<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 儀表板報表控制器
 *
 * 提供儀表板相關的 API 端點
 */
class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * 取得儀表板總覽
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->dashboardService->getOverview(
                $request->input('start_date'),
                $request->input('end_date')
            );

            return $this->success($data, '取得儀表板數據成功');
        } catch (\Exception $e) {
            return $this->serverError('取得儀表板數據失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得今日數據
     */
    public function today(): JsonResponse
    {
        try {
            $data = $this->dashboardService->getTodayData();

            return $this->success($data, '取得今日數據成功');
        } catch (\Exception $e) {
            return $this->serverError('取得今日數據失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得趨勢數據
     */
    public function trends(Request $request): JsonResponse
    {
        try {
            $days = $request->input('days', 30);
            $data = $this->dashboardService->getTrends($days);

            return $this->success($data, '取得趨勢數據成功');
        } catch (\Exception $e) {
            return $this->serverError('取得趨勢數據失敗：'.$e->getMessage());
        }
    }
}
