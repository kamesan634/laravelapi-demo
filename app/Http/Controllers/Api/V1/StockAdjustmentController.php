<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustment\StoreStockAdjustmentRequest;
use App\Http\Requests\StockAdjustment\UpdateStockAdjustmentRequest;
use App\Models\Inventory;
use App\Models\StockAdjustment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 庫存調整控制器
 *
 * 處理庫存調整的 CRUD 操作及審核
 */
class StockAdjustmentController extends Controller
{
    use ApiResponse;

    /**
     * 取得調整單列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockAdjustment::with(['warehouse', 'product', 'variant', 'creator', 'approver']);

        // 搜尋條件
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('adjustment_no', 'like', "%{$keyword}%")
                    ->orWhereHas('product', function ($pq) use ($keyword) {
                        $pq->where('name', 'like', "%{$keyword}%")
                            ->orWhere('sku', 'like', "%{$keyword}%");
                    });
            });
        }

        // 倉庫篩選
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // 調整類型篩選
        if ($request->filled('adjustment_type')) {
            $query->where('adjustment_type', $request->adjustment_type);
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('adjustment_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('adjustment_date', '<=', $request->end_date);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $adjustments = $query->paginate($perPage);

        return $this->paginated($adjustments, '查詢調整單列表成功');
    }

    /**
     * 新增調整單
     *
     * @param  StoreStockAdjustmentRequest  $request  新增請求
     */
    public function store(StoreStockAdjustmentRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // 取得目前庫存
            $inventory = Inventory::where('warehouse_id', $data['warehouse_id'])
                ->where('product_id', $data['product_id'])
                ->where('variant_id', $data['variant_id'] ?? null)
                ->first();

            $beforeQuantity = $inventory ? $inventory->quantity : 0;
            $afterQuantity = $beforeQuantity + $data['adjust_quantity'];

            // 檢查調整後庫存是否為負
            if ($afterQuantity < 0) {
                return $this->error('調整後庫存不能為負數', 422);
            }

            // 生成調整單編號
            $adjustmentNo = 'ADJ'.date('Ymd').str_pad(
                StockAdjustment::whereDate('created_at', today())->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            // 計算調整金額
            $unitCost = $data['unit_cost'] ?? 0;
            $adjustmentValue = $data['adjust_quantity'] * $unitCost;

            // 建立調整單
            $adjustment = StockAdjustment::create([
                'adjustment_no' => $adjustmentNo,
                'warehouse_id' => $data['warehouse_id'],
                'adjustment_date' => $data['adjustment_date'],
                'adjustment_type' => $data['adjustment_type'],
                'source_type' => 'MANUAL',
                'source_id' => null,
                'product_id' => $data['product_id'],
                'variant_id' => $data['variant_id'] ?? null,
                'before_quantity' => $beforeQuantity,
                'adjust_quantity' => $data['adjust_quantity'],
                'after_quantity' => $afterQuantity,
                'unit_cost' => $unitCost,
                'adjustment_value' => $adjustmentValue,
                'status' => 'PENDING',
                'reason' => $data['reason'],
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            $adjustment->load(['warehouse', 'product', 'variant', 'creator']);

            return $this->created($adjustment, '調整單建立成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('調整單建立失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得單一調整單
     *
     * @param  StockAdjustment  $stockAdjustment  調整單
     */
    public function show(StockAdjustment $stockAdjustment): JsonResponse
    {
        $stockAdjustment->load(['warehouse', 'product', 'variant', 'creator', 'approver']);

        return $this->success($stockAdjustment, '取得調整單詳情成功');
    }

    /**
     * 更新調整單
     *
     * @param  UpdateStockAdjustmentRequest  $request  更新請求
     * @param  StockAdjustment  $stockAdjustment  調整單
     */
    public function update(UpdateStockAdjustmentRequest $request, StockAdjustment $stockAdjustment): JsonResponse
    {
        // 只允許更新待審核狀態的調整單
        if ($stockAdjustment->status !== 'PENDING') {
            return $this->error('只能更新待審核狀態的調整單', 422);
        }

        $data = $request->validated();

        // 重新計算調整後數量
        if (isset($data['adjust_quantity'])) {
            $afterQuantity = $stockAdjustment->before_quantity + $data['adjust_quantity'];
            if ($afterQuantity < 0) {
                return $this->error('調整後庫存不能為負數', 422);
            }
            $data['after_quantity'] = $afterQuantity;
        }

        $stockAdjustment->update($data);
        $stockAdjustment->load(['warehouse', 'product', 'variant', 'creator']);

        return $this->success($stockAdjustment, '調整單更新成功');
    }

    /**
     * 刪除調整單
     *
     * @param  StockAdjustment  $stockAdjustment  調整單
     */
    public function destroy(StockAdjustment $stockAdjustment): JsonResponse
    {
        // 只允許刪除待審核狀態的調整單
        if ($stockAdjustment->status !== 'PENDING') {
            return $this->error('只能刪除待審核狀態的調整單', 422);
        }

        $stockAdjustment->delete();

        return $this->success(null, '調整單刪除成功');
    }

    /**
     * 審核調整單
     *
     * @param  StockAdjustment  $stockAdjustment  調整單
     */
    public function approve(StockAdjustment $stockAdjustment): JsonResponse
    {
        if ($stockAdjustment->status !== 'PENDING') {
            return $this->error('只能審核待審核狀態的調整單', 422);
        }

        try {
            DB::beginTransaction();

            // 更新或建立庫存記錄
            $inventory = Inventory::firstOrNew([
                'warehouse_id' => $stockAdjustment->warehouse_id,
                'product_id' => $stockAdjustment->product_id,
                'variant_id' => $stockAdjustment->variant_id,
            ]);

            // 重新檢查當前庫存（可能已變動）
            $currentQuantity = $inventory->exists ? $inventory->quantity : 0;
            $newQuantity = $currentQuantity + $stockAdjustment->adjust_quantity;

            if ($newQuantity < 0) {
                return $this->error('調整後庫存不能為負數', 422);
            }

            // 更新調整單的實際調整數值
            $stockAdjustment->before_quantity = $currentQuantity;
            $stockAdjustment->after_quantity = $newQuantity;
            $stockAdjustment->status = 'APPROVED';
            $stockAdjustment->approved_by = auth()->id();
            $stockAdjustment->approved_at = now();
            $stockAdjustment->save();

            // 更新庫存
            $inventory->quantity = $newQuantity;
            $inventory->last_movement_date = now();
            $inventory->save();

            DB::commit();

            $stockAdjustment->load(['warehouse', 'product', 'variant', 'approver']);

            return $this->success($stockAdjustment, '調整單審核通過，庫存已更新');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('調整單審核失敗：'.$e->getMessage());
        }
    }
}
