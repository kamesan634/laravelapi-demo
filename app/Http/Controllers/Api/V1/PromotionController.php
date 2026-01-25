<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotion\StorePromotionRequest;
use App\Http\Requests\Promotion\UpdatePromotionRequest;
use App\Models\Promotion;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 促銷活動控制器
 *
 * 處理促銷活動的 CRUD 操作
 */
class PromotionController extends Controller
{
    use ApiResponse;

    /**
     * 取得促銷活動列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Promotion::with('creator');

        // 搜尋條件
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        // 促銷類型篩選
        if ($request->filled('promotion_type')) {
            $query->where('promotion_type', $request->promotion_type);
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 進行中篩選
        if ($request->filled('ongoing') && $request->boolean('ongoing')) {
            $now = now();
            $query->where('status', 'ACTIVE')
                ->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $promotions = $query->paginate($perPage);

        return $this->paginated($promotions, '查詢促銷活動列表成功');
    }

    /**
     * 新增促銷活動
     *
     * @param  StorePromotionRequest  $request  新增請求
     */
    public function store(StorePromotionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        $data['current_usage'] = 0;

        $promotion = Promotion::create($data);
        $promotion->load('creator');

        return $this->created($promotion, '促銷活動建立成功');
    }

    /**
     * 取得單一促銷活動
     *
     * @param  Promotion  $promotion  促銷活動
     */
    public function show(Promotion $promotion): JsonResponse
    {
        $promotion->load('creator');

        return $this->success($promotion, '取得促銷活動詳情成功');
    }

    /**
     * 更新促銷活動
     *
     * @param  UpdatePromotionRequest  $request  更新請求
     * @param  Promotion  $promotion  促銷活動
     */
    public function update(UpdatePromotionRequest $request, Promotion $promotion): JsonResponse
    {
        $promotion->update($request->validated());
        $promotion->load('creator');

        return $this->success($promotion, '促銷活動更新成功');
    }

    /**
     * 刪除促銷活動
     *
     * @param  Promotion  $promotion  促銷活動
     */
    public function destroy(Promotion $promotion): JsonResponse
    {
        // 檢查是否已被使用
        if ($promotion->current_usage > 0) {
            return $this->error('此促銷活動已被使用，無法刪除', 422);
        }

        $promotion->delete();

        return $this->success(null, '促銷活動刪除成功');
    }

    /**
     * 啟用/停用促銷活動
     *
     * @param  Promotion  $promotion  促銷活動
     */
    public function toggle(Promotion $promotion): JsonResponse
    {
        $promotion->status = $promotion->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $promotion->save();

        $statusText = $promotion->status === 'ACTIVE' ? '啟用' : '停用';

        return $this->success($promotion, "促銷活動已{$statusText}");
    }
}
