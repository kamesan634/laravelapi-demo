<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\StorePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\UpdatePurchaseOrderRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 採購單控制器
 *
 * 處理採購單的 CRUD 操作及審核流程
 */
class PurchaseOrderController extends Controller
{
    use ApiResponse;

    /**
     * 取得採購單列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseOrder::with(['supplier', 'warehouse', 'creator', 'approver']);

        // 搜尋條件
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('po_no', 'like', "%{$keyword}%")
                    ->orWhereHas('supplier', function ($sq) use ($keyword) {
                        $sq->where('name', 'like', "%{$keyword}%");
                    });
            });
        }

        // 供應商篩選
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // 倉庫篩選
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('order_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('order_date', '<=', $request->end_date);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $purchaseOrders = $query->paginate($perPage);

        return $this->paginated($purchaseOrders, '查詢採購單列表成功');
    }

    /**
     * 新增採購單
     *
     * @param  StorePurchaseOrderRequest  $request  新增請求
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // 生成採購單編號
            $poNo = 'PO'.date('Ymd').str_pad(
                PurchaseOrder::whereDate('created_at', today())->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            // 計算金額
            $subtotal = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = $item['quantity'];
                $unitPrice = $item['unit_price'];
                $taxRate = $item['tax_rate'] ?? 5;
                $lineTotal = $unitPrice * $quantity;

                $subtotal += $lineTotal;

                $itemsData[] = [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $quantity,
                    'received_quantity' => 0,
                    'unit_price' => $unitPrice,
                    'discount_rate' => 0,
                    'line_total' => $lineTotal,
                ];
            }

            $taxAmount = round($subtotal * 0.05, 2);
            $totalAmount = $subtotal + $taxAmount;

            // 建立採購單
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => $poNo,
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'status' => 'DRAFT',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $data['remark'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // 建立採購明細
            foreach ($itemsData as $itemData) {
                $itemData['po_id'] = $purchaseOrder->id;
                PurchaseOrderItem::create($itemData);
            }

            DB::commit();

            $purchaseOrder->load(['supplier', 'warehouse', 'items.product', 'creator']);

            return $this->created($purchaseOrder, '採購單建立成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('採購單建立失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得單一採購單
     *
     * @param  PurchaseOrder  $purchaseOrder  採購單
     */
    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->load(['supplier', 'warehouse', 'items.product', 'items.variant', 'creator', 'approver', 'receipts']);

        return $this->success($purchaseOrder, '取得採購單詳情成功');
    }

    /**
     * 更新採購單
     *
     * @param  UpdatePurchaseOrderRequest  $request  更新請求
     * @param  PurchaseOrder  $purchaseOrder  採購單
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        // 只允許更新草稿狀態的採購單
        if ($purchaseOrder->status !== 'DRAFT') {
            return $this->error('只能更新草稿狀態的採購單', 422);
        }

        $purchaseOrder->update($request->validated());
        $purchaseOrder->load(['supplier', 'warehouse', 'items.product', 'creator']);

        return $this->success($purchaseOrder, '採購單更新成功');
    }

    /**
     * 刪除採購單
     *
     * @param  PurchaseOrder  $purchaseOrder  採購單
     */
    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        // 只允許刪除草稿或已取消的採購單
        if (! in_array($purchaseOrder->status, ['DRAFT', 'CANCELLED'])) {
            return $this->error('只能刪除草稿或已取消的採購單', 422);
        }

        try {
            DB::beginTransaction();

            $purchaseOrder->items()->delete();
            $purchaseOrder->delete();

            DB::commit();

            return $this->success(null, '採購單刪除成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('採購單刪除失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得採購明細
     *
     * @param  PurchaseOrder  $purchaseOrder  採購單
     */
    public function items(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $items = $purchaseOrder->items()->with(['product', 'variant'])->get();

        return $this->success($items, '取得採購明細成功');
    }

    /**
     * 送審
     *
     * @param  PurchaseOrder  $purchaseOrder  採購單
     */
    public function submit(PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->status !== 'DRAFT') {
            return $this->error('只能送審草稿狀態的採購單', 422);
        }

        $purchaseOrder->status = 'PENDING';
        $purchaseOrder->save();

        return $this->success($purchaseOrder, '採購單已送審');
    }

    /**
     * 審核通過
     *
     * @param  PurchaseOrder  $purchaseOrder  採購單
     */
    public function approve(PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->status !== 'PENDING') {
            return $this->error('只能審核待審核狀態的採購單', 422);
        }

        $purchaseOrder->status = 'APPROVED';
        $purchaseOrder->approved_by = auth()->id();
        $purchaseOrder->approved_at = now();
        $purchaseOrder->save();

        $purchaseOrder->load('approver');

        return $this->success($purchaseOrder, '採購單審核通過');
    }

    /**
     * 審核駁回
     *
     * @param  PurchaseOrder  $purchaseOrder  採購單
     */
    public function reject(PurchaseOrder $purchaseOrder): JsonResponse
    {
        if ($purchaseOrder->status !== 'PENDING') {
            return $this->error('只能駁回待審核狀態的採購單', 422);
        }

        $purchaseOrder->status = 'CANCELLED';
        $purchaseOrder->approved_by = auth()->id();
        $purchaseOrder->approved_at = now();
        $purchaseOrder->save();

        return $this->success($purchaseOrder, '採購單已駁回');
    }

    /**
     * 取消採購單
     *
     * @param  PurchaseOrder  $purchaseOrder  採購單
     */
    public function cancel(PurchaseOrder $purchaseOrder): JsonResponse
    {
        // 只能取消草稿或待審核的採購單
        if (! in_array($purchaseOrder->status, ['DRAFT', 'PENDING', 'APPROVED'])) {
            return $this->error('此狀態的採購單無法取消', 422);
        }

        // 檢查是否有收貨記錄
        if ($purchaseOrder->receipts()->exists()) {
            return $this->error('此採購單已有收貨記錄，無法取消', 422);
        }

        $purchaseOrder->status = 'CANCELLED';
        $purchaseOrder->save();

        return $this->success($purchaseOrder, '採購單已取消');
    }
}
