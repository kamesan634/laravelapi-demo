<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockTransfer\StoreStockTransferRequest;
use App\Http\Requests\StockTransfer\UpdateStockTransferRequest;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\StockTransfer;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 調撥單控制器
 *
 * 處理庫存調撥的 CRUD 操作及調撥流程
 */
class StockTransferController extends Controller
{
    use ApiResponse;

    /**
     * 取得調撥單列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'items', 'creator']);

        // 關鍵字搜尋
        if ($request->filled('keyword')) {
            $query->where('transfer_no', 'like', "%{$request->keyword}%");
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 來源倉庫篩選
        if ($request->filled('from_warehouse_id')) {
            $query->where('from_warehouse_id', $request->from_warehouse_id);
        }

        // 目的倉庫篩選
        if ($request->filled('to_warehouse_id')) {
            $query->where('to_warehouse_id', $request->to_warehouse_id);
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('transfer_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('transfer_date', '<=', $request->end_date);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $transfers = $query->paginate($perPage);

        return $this->paginated($transfers, '查詢調撥單列表成功');
    }

    /**
     * 新增調撥單
     *
     * @param  StoreStockTransferRequest  $request  新增請求
     */
    public function store(StoreStockTransferRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['created_by'] = auth()->id();
            $data['transfer_no'] = 'TRF'.date('Ymd').str_pad(StockTransfer::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

            // 欄位映射：remark -> notes
            if (isset($data['remark'])) {
                $data['notes'] = $data['remark'];
                unset($data['remark']);
            }

            // 移除不存在的欄位
            unset($data['expected_date']);

            $items = $data['items'] ?? [];
            unset($data['items']);

            $transfer = StockTransfer::create($data);

            // 建立調撥明細
            foreach ($items as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'creator']);

            return $this->created($transfer, '調撥單建立成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('調撥單建立失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得單一調撥單
     *
     * @param  StockTransfer  $stockTransfer  調撥單
     */
    public function show(StockTransfer $stockTransfer): JsonResponse
    {
        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'creator', 'approver']);

        return $this->success($stockTransfer, '取得調撥單詳情成功');
    }

    /**
     * 更新調撥單
     *
     * @param  UpdateStockTransferRequest  $request  更新請求
     * @param  StockTransfer  $stockTransfer  調撥單
     */
    public function update(UpdateStockTransferRequest $request, StockTransfer $stockTransfer): JsonResponse
    {
        if (! in_array($stockTransfer->status, ['DRAFT', 'PENDING'])) {
            return $this->error('只能更新草稿或待審核的調撥單', 422);
        }

        $stockTransfer->update($request->validated());
        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'creator']);

        return $this->success($stockTransfer, '調撥單更新成功');
    }

    /**
     * 刪除調撥單
     *
     * @param  StockTransfer  $stockTransfer  調撥單
     */
    public function destroy(StockTransfer $stockTransfer): JsonResponse
    {
        if (! in_array($stockTransfer->status, ['DRAFT', 'PENDING', 'CANCELLED'])) {
            return $this->error('只能刪除草稿、待審核或已取消的調撥單', 422);
        }

        try {
            DB::beginTransaction();
            $stockTransfer->items()->delete();
            $stockTransfer->delete();
            DB::commit();

            return $this->success(null, '調撥單刪除成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('調撥單刪除失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得調撥明細
     *
     * @param  StockTransfer  $stockTransfer  調撥單
     */
    public function items(StockTransfer $stockTransfer): JsonResponse
    {
        $items = $stockTransfer->items()->with(['product', 'variant'])->get();

        return $this->success($items, '取得調撥明細成功');
    }

    /**
     * 審核通過
     *
     * @param  StockTransfer  $stockTransfer  調撥單
     */
    public function approve(StockTransfer $stockTransfer): JsonResponse
    {
        if ($stockTransfer->status !== 'PENDING') {
            return $this->error('只能審核待審核的調撥單', 422);
        }

        $stockTransfer->update([
            'status' => 'APPROVED',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'creator', 'approver']);

        return $this->success($stockTransfer, '調撥單審核通過');
    }

    /**
     * 出貨（從來源倉出庫）
     *
     * @param  StockTransfer  $stockTransfer  調撥單
     */
    public function ship(StockTransfer $stockTransfer): JsonResponse
    {
        if ($stockTransfer->status !== 'APPROVED') {
            return $this->error('只能對已審核的調撥單進行出貨', 422);
        }

        try {
            DB::beginTransaction();

            // 從來源倉扣減庫存
            foreach ($stockTransfer->items as $item) {
                $inventory = Inventory::where('product_id', $item->product_id)
                    ->where('warehouse_id', $stockTransfer->from_warehouse_id)
                    ->first();

                if ($inventory) {
                    $beforeQty = $inventory->quantity;
                    $inventory->decrement('quantity', $item->quantity);

                    // 記錄庫存異動
                    InventoryMovement::create([
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'warehouse_id' => $stockTransfer->from_warehouse_id,
                        'movement_type' => 'TRANSFER_OUT',
                        'quantity' => -$item->quantity,
                        'before_quantity' => $beforeQty,
                        'after_quantity' => $beforeQty - $item->quantity,
                        'reference_type' => 'STOCK_TRANSFER',
                        'reference_id' => $stockTransfer->id,
                        'reference_no' => $stockTransfer->transfer_no,
                        'notes' => '調撥出庫',
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            $stockTransfer->update([
                'status' => 'IN_TRANSIT',
                'shipped_by' => auth()->id(),
                'shipped_at' => now(),
            ]);

            DB::commit();

            $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'creator']);

            return $this->success($stockTransfer, '調撥單出貨成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('調撥單出貨失敗：'.$e->getMessage());
        }
    }

    /**
     * 收貨（入目的倉）
     *
     * @param  StockTransfer  $stockTransfer  調撥單
     */
    public function receive(StockTransfer $stockTransfer): JsonResponse
    {
        if ($stockTransfer->status !== 'IN_TRANSIT') {
            return $this->error('只能對運送中的調撥單進行收貨', 422);
        }

        try {
            DB::beginTransaction();

            // 入目的倉增加庫存
            foreach ($stockTransfer->items as $item) {
                $inventory = Inventory::firstOrCreate(
                    [
                        'product_id' => $item->product_id,
                        'warehouse_id' => $stockTransfer->to_warehouse_id,
                    ],
                    ['quantity' => 0]
                );

                $beforeQty = $inventory->quantity;
                $inventory->increment('quantity', $item->quantity);

                // 記錄庫存異動
                InventoryMovement::create([
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'warehouse_id' => $stockTransfer->to_warehouse_id,
                    'movement_type' => 'TRANSFER_IN',
                    'quantity' => $item->quantity,
                    'before_quantity' => $beforeQty,
                    'after_quantity' => $beforeQty + $item->quantity,
                    'reference_type' => 'STOCK_TRANSFER',
                    'reference_id' => $stockTransfer->id,
                    'reference_no' => $stockTransfer->transfer_no,
                    'notes' => '調撥入庫',
                    'created_by' => auth()->id(),
                ]);
            }

            $stockTransfer->update([
                'status' => 'COMPLETED',
                'received_by' => auth()->id(),
                'received_at' => now(),
            ]);

            DB::commit();

            $stockTransfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'creator']);

            return $this->success($stockTransfer, '調撥單收貨成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('調撥單收貨失敗：'.$e->getMessage());
        }
    }
}
