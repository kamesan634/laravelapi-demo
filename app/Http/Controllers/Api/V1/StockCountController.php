<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockCount\StoreStockCountItemRequest;
use App\Http\Requests\StockCount\StoreStockCountRequest;
use App\Http\Requests\StockCount\UpdateStockCountItemRequest;
use App\Http\Requests\StockCount\UpdateStockCountRequest;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 盤點控制器
 *
 * 處理盤點單的 CRUD 操作及盤點流程
 */
class StockCountController extends Controller
{
    use ApiResponse;

    /**
     * 取得盤點單列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockCount::with(['warehouse', 'items', 'creator']);

        // 關鍵字搜尋
        if ($request->filled('keyword')) {
            $query->where('count_no', 'like', "%{$request->keyword}%");
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 倉庫篩選
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('count_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('count_date', '<=', $request->end_date);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $counts = $query->paginate($perPage);

        return $this->paginated($counts, '查詢盤點單列表成功');
    }

    /**
     * 新增盤點單
     *
     * @param  StoreStockCountRequest  $request  新增請求
     */
    public function store(StoreStockCountRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['created_by'] = auth()->id();
            $data['count_no'] = 'SC'.date('YmdHis').str_pad(StockCount::count() + 1, 4, '0', STR_PAD_LEFT);

            // 欄位映射：remark -> notes
            if (isset($data['remark'])) {
                $data['notes'] = $data['remark'];
                unset($data['remark']);
            }

            $items = $data['items'] ?? [];
            unset($data['items']);

            $count = StockCount::create($data);

            // 建立盤點明細
            foreach ($items as $item) {
                // 取得系統庫存
                $inventory = Inventory::where('product_id', $item['product_id'])
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->first();

                $count->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'system_quantity' => $inventory?->quantity ?? 0,
                    'counted_quantity' => $item['counted_quantity'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            $count->load(['warehouse', 'items.product', 'creator']);

            return $this->created($count, '盤點單建立成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('盤點單建立失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得單一盤點單
     *
     * @param  StockCount  $stockCount  盤點單
     */
    public function show(StockCount $stockCount): JsonResponse
    {
        $stockCount->load(['warehouse', 'items.product', 'creator', 'approver']);

        return $this->success($stockCount, '取得盤點單詳情成功');
    }

    /**
     * 更新盤點單
     *
     * @param  UpdateStockCountRequest  $request  更新請求
     * @param  StockCount  $stockCount  盤點單
     */
    public function update(UpdateStockCountRequest $request, StockCount $stockCount): JsonResponse
    {
        if (! in_array($stockCount->status, ['DRAFT', 'PENDING', 'COUNTING'])) {
            return $this->error('只能更新草稿、待處理或進行中的盤點單', 422);
        }

        $data = $request->validated();
        // 欄位映射：remark -> notes
        if (isset($data['remark'])) {
            $data['notes'] = $data['remark'];
            unset($data['remark']);
        }

        $stockCount->update($data);
        $stockCount->load(['warehouse', 'items.product', 'creator']);

        return $this->success($stockCount, '盤點單更新成功');
    }

    /**
     * 刪除盤點單
     *
     * @param  StockCount  $stockCount  盤點單
     */
    public function destroy(StockCount $stockCount): JsonResponse
    {
        if (! in_array($stockCount->status, ['DRAFT', 'PENDING', 'CANCELLED'])) {
            return $this->error('只能刪除草稿、待處理或已取消的盤點單', 422);
        }

        try {
            DB::beginTransaction();
            $stockCount->items()->delete();
            $stockCount->delete();
            DB::commit();

            return $this->success(null, '盤點單刪除成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('盤點單刪除失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得盤點明細
     *
     * @param  StockCount  $stockCount  盤點單
     */
    public function items(StockCount $stockCount): JsonResponse
    {
        $items = $stockCount->items()->with(['product', 'variant'])->get();

        return $this->success($items, '取得盤點明細成功');
    }

    /**
     * 新增盤點項目
     *
     * @param  StoreStockCountItemRequest  $request  新增請求
     * @param  StockCount  $stockCount  盤點單
     */
    public function addItem(StoreStockCountItemRequest $request, StockCount $stockCount): JsonResponse
    {
        if (! in_array($stockCount->status, ['DRAFT', 'PENDING', 'COUNTING'])) {
            return $this->error('只能在草稿、待處理或進行中的盤點單新增項目', 422);
        }

        $data = $request->validated();
        // 欄位映射：remark -> notes
        if (isset($data['remark'])) {
            $data['notes'] = $data['remark'];
            unset($data['remark']);
        }

        // 取得系統庫存
        $inventory = Inventory::where('product_id', $data['product_id'])
            ->where('warehouse_id', $stockCount->warehouse_id)
            ->first();

        $item = $stockCount->items()->create([
            'product_id' => $data['product_id'],
            'variant_id' => $data['variant_id'] ?? null,
            'system_quantity' => $inventory?->quantity ?? 0,
            'counted_quantity' => $data['counted_quantity'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $item->load(['product', 'variant']);

        return $this->created($item, '盤點項目新增成功');
    }

    /**
     * 更新盤點數量
     *
     * @param  UpdateStockCountItemRequest  $request  更新請求
     * @param  StockCount  $stockCount  盤點單
     * @param  StockCountItem  $item  盤點項目
     */
    public function updateItem(UpdateStockCountItemRequest $request, StockCount $stockCount, StockCountItem $item): JsonResponse
    {
        if (! in_array($stockCount->status, ['DRAFT', 'PENDING', 'COUNTING'])) {
            return $this->error('只能在草稿、待處理或進行中的盤點單更新項目', 422);
        }

        if ($item->count_id !== $stockCount->id) {
            return $this->error('盤點項目不屬於此盤點單', 404);
        }

        $data = $request->validated();
        // 欄位映射：remark -> notes
        if (isset($data['remark'])) {
            $data['notes'] = $data['remark'];
            unset($data['remark']);
        }

        $item->update($data);
        $item->load(['product', 'variant']);

        return $this->success($item, '盤點數量更新成功');
    }

    /**
     * 完成盤點
     *
     * @param  StockCount  $stockCount  盤點單
     */
    public function complete(StockCount $stockCount): JsonResponse
    {
        if ($stockCount->status !== 'COUNTING') {
            return $this->error('只能完成進行中的盤點單', 422);
        }

        // 檢查是否所有項目都已盤點
        $uncountedItems = $stockCount->items()->whereNull('counted_quantity')->count();
        if ($uncountedItems > 0) {
            return $this->error("還有 {$uncountedItems} 個項目尚未盤點", 422);
        }

        try {
            DB::beginTransaction();

            // 調整庫存並記錄異動
            foreach ($stockCount->items as $item) {
                $difference = $item->counted_quantity - $item->system_quantity;

                if ($difference != 0) {
                    // 更新庫存
                    $inventory = Inventory::firstOrCreate(
                        [
                            'product_id' => $item->product_id,
                            'warehouse_id' => $stockCount->warehouse_id,
                        ],
                        ['quantity' => 0]
                    );

                    $beforeQty = $inventory->quantity;
                    $inventory->update(['quantity' => $item->counted_quantity]);

                    // 記錄庫存異動
                    $movementType = $difference > 0 ? 'COUNT_IN' : 'COUNT_OUT';
                    InventoryMovement::create([
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'warehouse_id' => $stockCount->warehouse_id,
                        'movement_type' => $movementType,
                        'quantity' => $difference,
                        'before_quantity' => $beforeQty,
                        'after_quantity' => $item->counted_quantity,
                        'reference_type' => 'STOCK_COUNT',
                        'reference_id' => $stockCount->id,
                        'reference_no' => $stockCount->count_no,
                        'notes' => $difference > 0 ? '盤盈' : '盤虧',
                        'created_by' => auth()->id(),
                    ]);

                    // 更新盤點項目的差異數量
                    $item->update(['difference_quantity' => $difference]);
                }
            }

            // 更新盤點單狀態
            $stockCount->update([
                'status' => 'COMPLETED',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            $stockCount->load(['warehouse', 'items.product', 'creator', 'approver']);

            return $this->success($stockCount, '盤點完成');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('完成盤點失敗：'.$e->getMessage());
        }
    }
}
