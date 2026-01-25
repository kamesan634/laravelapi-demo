<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GoodsReceipt\StoreGoodsReceiptRequest;
use App\Http\Requests\GoodsReceipt\UpdateGoodsReceiptRequest;
use App\Models\GoodsReceipt;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 進貨單控制器
 *
 * 處理進貨單的 CRUD 操作及確認入庫
 */
class GoodsReceiptController extends Controller
{
    use ApiResponse;

    /**
     * 取得進貨單列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = GoodsReceipt::with(['warehouse', 'items', 'creator']);

        // 關鍵字搜尋
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('receipt_no', 'like', "%{$keyword}%")
                    ->orWhere('source_no', 'like', "%{$keyword}%");
            });
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 入庫類型篩選
        if ($request->filled('receipt_type')) {
            $query->where('receipt_type', $request->receipt_type);
        }

        // 倉庫篩選
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('receipt_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('receipt_date', '<=', $request->end_date);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $receipts = $query->paginate($perPage);

        return $this->paginated($receipts, '查詢進貨單列表成功');
    }

    /**
     * 新增進貨單
     *
     * @param  StoreGoodsReceiptRequest  $request  新增請求
     */
    public function store(StoreGoodsReceiptRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['created_by'] = auth()->id();
            $data['receipt_no'] = 'GR'.date('YmdHis').str_pad(GoodsReceipt::count() + 1, 4, '0', STR_PAD_LEFT);

            $items = $data['items'] ?? [];
            unset($data['items']);

            $receipt = GoodsReceipt::create($data);

            // 建立進貨明細
            foreach ($items as $item) {
                $receipt->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'expected_quantity' => $item['expected_quantity'] ?? $item['quantity'],
                    'received_quantity' => $item['received_quantity'] ?? $item['quantity'],
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            $receipt->load(['warehouse', 'items.product', 'creator']);

            return $this->created($receipt, '進貨單建立成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('進貨單建立失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得單一進貨單
     *
     * @param  GoodsReceipt  $goodsReceipt  進貨單
     */
    public function show(GoodsReceipt $goodsReceipt): JsonResponse
    {
        $goodsReceipt->load(['warehouse', 'items.product', 'creator', 'approver']);

        return $this->success($goodsReceipt, '取得進貨單詳情成功');
    }

    /**
     * 更新進貨單
     *
     * @param  UpdateGoodsReceiptRequest  $request  更新請求
     * @param  GoodsReceipt  $goodsReceipt  進貨單
     */
    public function update(UpdateGoodsReceiptRequest $request, GoodsReceipt $goodsReceipt): JsonResponse
    {
        if ($goodsReceipt->status !== 'PENDING') {
            return $this->error('只能更新待處理的進貨單', 422);
        }

        $goodsReceipt->update($request->validated());
        $goodsReceipt->load(['warehouse', 'items.product', 'creator']);

        return $this->success($goodsReceipt, '進貨單更新成功');
    }

    /**
     * 刪除進貨單
     *
     * @param  GoodsReceipt  $goodsReceipt  進貨單
     */
    public function destroy(GoodsReceipt $goodsReceipt): JsonResponse
    {
        if ($goodsReceipt->status !== 'PENDING') {
            return $this->error('只能刪除待處理的進貨單', 422);
        }

        try {
            DB::beginTransaction();
            $goodsReceipt->items()->delete();
            $goodsReceipt->delete();
            DB::commit();

            return $this->success(null, '進貨單刪除成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('進貨單刪除失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得進貨明細
     *
     * @param  GoodsReceipt  $goodsReceipt  進貨單
     */
    public function items(GoodsReceipt $goodsReceipt): JsonResponse
    {
        $items = $goodsReceipt->items()->with(['product', 'variant'])->get();

        return $this->success($items, '取得進貨明細成功');
    }

    /**
     * 確認入庫
     *
     * @param  GoodsReceipt  $goodsReceipt  進貨單
     */
    public function confirm(GoodsReceipt $goodsReceipt): JsonResponse
    {
        if ($goodsReceipt->status !== 'PENDING') {
            return $this->error('只能確認待處理的進貨單', 422);
        }

        try {
            DB::beginTransaction();

            // 更新庫存並記錄異動
            foreach ($goodsReceipt->items as $item) {
                // 增加庫存
                $inventory = Inventory::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'warehouse_id' => $goodsReceipt->warehouse_id,
                    ],
                    ['quantity' => 0]
                );

                $beforeQty = $inventory->quantity;
                $inventory->increment('quantity', $item->received_quantity);

                // 記錄庫存異動
                InventoryMovement::create([
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'warehouse_id' => $goodsReceipt->warehouse_id,
                    'movement_type' => 'PURCHASE_IN',
                    'quantity' => $item->received_quantity,
                    'before_quantity' => $beforeQty,
                    'after_quantity' => $beforeQty + $item->received_quantity,
                    'unit_cost' => $item->unit_cost,
                    'reference_type' => 'GOODS_RECEIPT',
                    'reference_id' => $goodsReceipt->id,
                    'reference_no' => $goodsReceipt->receipt_no,
                    'created_by' => auth()->id(),
                ]);
            }

            // 更新進貨單狀態
            $goodsReceipt->update([
                'status' => 'COMPLETED',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            $goodsReceipt->load(['warehouse', 'items.product', 'creator', 'approver']);

            return $this->success($goodsReceipt, '入庫確認成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('入庫確認失敗：'.$e->getMessage());
        }
    }
}
