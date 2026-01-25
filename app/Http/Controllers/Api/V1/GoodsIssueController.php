<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GoodsIssue\StoreGoodsIssueRequest;
use App\Http\Requests\GoodsIssue\UpdateGoodsIssueRequest;
use App\Models\GoodsIssue;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 出貨單控制器
 *
 * 處理出貨單的 CRUD 操作及確認出庫
 */
class GoodsIssueController extends Controller
{
    use ApiResponse;

    /**
     * 取得出貨單列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = GoodsIssue::with(['warehouse', 'items', 'creator']);

        // 關鍵字搜尋
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('issue_no', 'like', "%{$keyword}%")
                    ->orWhere('source_no', 'like', "%{$keyword}%");
            });
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 出庫類型篩選
        if ($request->filled('issue_type')) {
            $query->where('issue_type', $request->issue_type);
        }

        // 倉庫篩選
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('issue_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('issue_date', '<=', $request->end_date);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $issues = $query->paginate($perPage);

        return $this->paginated($issues, '查詢出貨單列表成功');
    }

    /**
     * 新增出貨單
     *
     * @param  StoreGoodsIssueRequest  $request  新增請求
     */
    public function store(StoreGoodsIssueRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['created_by'] = auth()->id();
            $data['issue_no'] = 'GI'.date('YmdHis').str_pad(GoodsIssue::count() + 1, 4, '0', STR_PAD_LEFT);

            $items = $data['items'] ?? [];
            unset($data['items']);

            $issue = GoodsIssue::create($data);

            // 建立出貨明細
            foreach ($items as $item) {
                $issue->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            $issue->load(['warehouse', 'items.product', 'creator']);

            return $this->created($issue, '出貨單建立成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('出貨單建立失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得單一出貨單
     *
     * @param  GoodsIssue  $goodsIssue  出貨單
     */
    public function show(GoodsIssue $goodsIssue): JsonResponse
    {
        $goodsIssue->load(['warehouse', 'items.product', 'creator', 'approver']);

        return $this->success($goodsIssue, '取得出貨單詳情成功');
    }

    /**
     * 更新出貨單
     *
     * @param  UpdateGoodsIssueRequest  $request  更新請求
     * @param  GoodsIssue  $goodsIssue  出貨單
     */
    public function update(UpdateGoodsIssueRequest $request, GoodsIssue $goodsIssue): JsonResponse
    {
        if ($goodsIssue->status !== 'PENDING') {
            return $this->error('只能更新待處理的出貨單', 422);
        }

        $goodsIssue->update($request->validated());
        $goodsIssue->load(['warehouse', 'items.product', 'creator']);

        return $this->success($goodsIssue, '出貨單更新成功');
    }

    /**
     * 刪除出貨單
     *
     * @param  GoodsIssue  $goodsIssue  出貨單
     */
    public function destroy(GoodsIssue $goodsIssue): JsonResponse
    {
        if ($goodsIssue->status !== 'PENDING') {
            return $this->error('只能刪除待處理的出貨單', 422);
        }

        try {
            DB::beginTransaction();
            $goodsIssue->items()->delete();
            $goodsIssue->delete();
            DB::commit();

            return $this->success(null, '出貨單刪除成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('出貨單刪除失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得出貨明細
     *
     * @param  GoodsIssue  $goodsIssue  出貨單
     */
    public function items(GoodsIssue $goodsIssue): JsonResponse
    {
        $items = $goodsIssue->items()->with(['product', 'variant'])->get();

        return $this->success($items, '取得出貨明細成功');
    }

    /**
     * 確認出庫
     *
     * @param  GoodsIssue  $goodsIssue  出貨單
     */
    public function confirm(GoodsIssue $goodsIssue): JsonResponse
    {
        if ($goodsIssue->status !== 'PENDING') {
            return $this->error('只能確認待處理的出貨單', 422);
        }

        try {
            DB::beginTransaction();

            // 更新庫存並記錄異動
            foreach ($goodsIssue->items as $item) {
                // 扣減庫存
                $inventory = Inventory::where('product_id', $item->product_id)
                    ->where('warehouse_id', $goodsIssue->warehouse_id)
                    ->first();

                if ($inventory) {
                    $beforeQty = $inventory->quantity;
                    $inventory->decrement('quantity', $item->quantity);

                    // 記錄庫存異動
                    InventoryMovement::create([
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'warehouse_id' => $goodsIssue->warehouse_id,
                        'movement_type' => 'SALES_OUT',
                        'quantity' => -$item->quantity,
                        'before_quantity' => $beforeQty,
                        'after_quantity' => $beforeQty - $item->quantity,
                        'unit_cost' => $item->unit_cost,
                        'reference_type' => 'GOODS_ISSUE',
                        'reference_id' => $goodsIssue->id,
                        'reference_no' => $goodsIssue->issue_no,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // 更新出貨單狀態
            $goodsIssue->update([
                'status' => 'COMPLETED',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            $goodsIssue->load(['warehouse', 'items.product', 'creator', 'approver']);

            return $this->success($goodsIssue, '出庫確認成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('出庫確認失敗：'.$e->getMessage());
        }
    }
}
