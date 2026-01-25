<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseReturn\StorePurchaseReturnRequest;
use App\Http\Requests\PurchaseReturn\UpdatePurchaseReturnRequest;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 採購退貨控制器
 *
 * 處理採購退貨的 CRUD 操作及審核流程
 */
class PurchaseReturnController extends Controller
{
    use ApiResponse;

    /**
     * 取得退貨單列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseReturn::with(['supplier', 'warehouse', 'receipt', 'creator', 'approver']);

        // 搜尋條件
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('return_no', 'like', "%{$keyword}%")
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

        // 退貨原因篩選
        if ($request->filled('return_reason')) {
            $query->where('return_reason', $request->return_reason);
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('return_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('return_date', '<=', $request->end_date);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $returns = $query->paginate($perPage);

        return $this->paginated($returns, '查詢退貨單列表成功');
    }

    /**
     * 新增退貨單
     *
     * @param  StorePurchaseReturnRequest  $request  新增請求
     */
    public function store(StorePurchaseReturnRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // 生成退貨單編號
            $returnNo = 'PR'.date('Ymd').str_pad(
                PurchaseReturn::whereDate('created_at', today())->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            // 處理退貨明細
            $totalAmount = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = $item['quantity'];
                $unitPrice = $item['unit_price'];
                $lineTotal = $unitPrice * $quantity;

                // 驗證庫存是否足夠
                $inventory = Inventory::where('warehouse_id', $data['warehouse_id'])
                    ->where('product_id', $item['product_id'])
                    ->where('variant_id', $item['variant_id'] ?? null)
                    ->first();

                $availableQty = $inventory ? $inventory->quantity : 0;
                if ($quantity > $availableQty) {
                    return $this->error("商品 {$product->name} 庫存不足，可退數量：{$availableQty}", 422);
                }

                $totalAmount += $lineTotal;

                $itemsData[] = [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'reason' => $item['reason'] ?? null,
                ];
            }

            // 建立退貨單
            $purchaseReturn = PurchaseReturn::create([
                'return_no' => $returnNo,
                'supplier_id' => $data['supplier_id'],
                'return_date' => $data['return_date'],
                'receipt_id' => $data['purchase_receipt_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'return_reason' => $data['return_reason'],
                'status' => 'PENDING',
                'total_amount' => $totalAmount,
                'notes' => $data['reason_detail'],
                'created_by' => auth()->id(),
            ]);

            // 建立退貨明細
            foreach ($itemsData as $itemData) {
                $itemData['return_id'] = $purchaseReturn->id;
                PurchaseReturnItem::create($itemData);
            }

            DB::commit();

            $purchaseReturn->load(['supplier', 'warehouse', 'items.product', 'creator']);

            return $this->created($purchaseReturn, '退貨單建立成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('退貨單建立失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得單一退貨單
     *
     * @param  PurchaseReturn  $purchaseReturn  退貨單
     */
    public function show(PurchaseReturn $purchaseReturn): JsonResponse
    {
        $purchaseReturn->load(['supplier', 'warehouse', 'receipt', 'items.product', 'items.variant', 'creator', 'approver']);

        return $this->success($purchaseReturn, '取得退貨單詳情成功');
    }

    /**
     * 更新退貨單
     *
     * @param  UpdatePurchaseReturnRequest  $request  更新請求
     * @param  PurchaseReturn  $purchaseReturn  退貨單
     */
    public function update(UpdatePurchaseReturnRequest $request, PurchaseReturn $purchaseReturn): JsonResponse
    {
        // 只允許更新待審核狀態的退貨單
        if ($purchaseReturn->status !== 'PENDING') {
            return $this->error('只能更新待審核狀態的退貨單', 422);
        }

        $purchaseReturn->update($request->validated());
        $purchaseReturn->load(['supplier', 'warehouse', 'items.product', 'creator']);

        return $this->success($purchaseReturn, '退貨單更新成功');
    }

    /**
     * 刪除退貨單
     *
     * @param  PurchaseReturn  $purchaseReturn  退貨單
     */
    public function destroy(PurchaseReturn $purchaseReturn): JsonResponse
    {
        // 只允許刪除待審核狀態的退貨單
        if ($purchaseReturn->status !== 'PENDING') {
            return $this->error('只能刪除待審核狀態的退貨單', 422);
        }

        try {
            DB::beginTransaction();

            $purchaseReturn->items()->delete();
            $purchaseReturn->delete();

            DB::commit();

            return $this->success(null, '退貨單刪除成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('退貨單刪除失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得退貨明細
     *
     * @param  PurchaseReturn  $purchaseReturn  退貨單
     */
    public function items(PurchaseReturn $purchaseReturn): JsonResponse
    {
        $items = $purchaseReturn->items()->with(['product', 'variant'])->get();

        return $this->success($items, '取得退貨明細成功');
    }

    /**
     * 審核退貨單
     *
     * @param  PurchaseReturn  $purchaseReturn  退貨單
     */
    public function approve(PurchaseReturn $purchaseReturn): JsonResponse
    {
        if ($purchaseReturn->status !== 'PENDING') {
            return $this->error('只能審核待審核狀態的退貨單', 422);
        }

        $purchaseReturn->status = 'APPROVED';
        $purchaseReturn->approved_by = auth()->id();
        $purchaseReturn->approved_at = now();
        $purchaseReturn->save();

        $purchaseReturn->load('approver');

        return $this->success($purchaseReturn, '退貨單審核通過');
    }

    /**
     * 出貨（退還供應商）
     *
     * @param  PurchaseReturn  $purchaseReturn  退貨單
     */
    public function ship(PurchaseReturn $purchaseReturn): JsonResponse
    {
        if ($purchaseReturn->status !== 'APPROVED') {
            return $this->error('只能出貨已審核通過的退貨單', 422);
        }

        try {
            DB::beginTransaction();

            // 扣減庫存
            foreach ($purchaseReturn->items as $item) {
                $inventory = Inventory::where('warehouse_id', $purchaseReturn->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->where('variant_id', $item->variant_id)
                    ->first();

                if (! $inventory || $inventory->quantity < $item->quantity) {
                    return $this->error('商品庫存不足，無法完成出貨', 422);
                }

                $inventory->quantity -= $item->quantity;
                $inventory->last_movement_date = now();
                $inventory->save();
            }

            $purchaseReturn->status = 'SHIPPED';
            $purchaseReturn->save();

            DB::commit();

            return $this->success($purchaseReturn, '退貨出貨成功，庫存已扣減');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('退貨出貨失敗：'.$e->getMessage());
        }
    }
}
