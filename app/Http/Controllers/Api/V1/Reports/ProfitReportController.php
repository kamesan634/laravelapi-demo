<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ProfitReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 利潤報表控制器
 *
 * 提供利潤報表相關的 API 端點
 */
class ProfitReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ProfitReportService $profitReportService
    ) {}

    /**
     * 取得利潤報表
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->profitReportService->getProfitReport($request->all());

            return $this->success($data, '取得利潤報表成功');
        } catch (\Exception $e) {
            return $this->serverError('取得利潤報表失敗：'.$e->getMessage());
        }
    }

    /**
     * 商品利潤分析
     */
    public function byProduct(Request $request): JsonResponse
    {
        try {
            $data = $this->profitReportService->getProfitByProduct($request->all());

            return $this->success($data, '取得商品利潤分析成功');
        } catch (\Exception $e) {
            return $this->serverError('取得商品利潤分析失敗：'.$e->getMessage());
        }
    }

    /**
     * 分類利潤統計
     */
    public function byCategory(Request $request): JsonResponse
    {
        try {
            $data = $this->profitReportService->getProfitByCategory($request->all());

            return $this->success($data, '取得分類利潤統計成功');
        } catch (\Exception $e) {
            return $this->serverError('取得分類利潤統計失敗：'.$e->getMessage());
        }
    }

    /**
     * 毛利率分析
     */
    public function margin(Request $request): JsonResponse
    {
        try {
            $data = $this->profitReportService->getMarginAnalysis($request->all());

            return $this->success($data, '取得毛利率分析成功');
        } catch (\Exception $e) {
            return $this->serverError('取得毛利率分析失敗：'.$e->getMessage());
        }
    }

    /**
     * 匯出利潤報表
     */
    public function export(Request $request): Response|JsonResponse
    {
        try {
            $data = $this->profitReportService->getExportData($request->all());

            $filename = 'profit_report_'.date('Y-m-d_His').'.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($data) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

                // Header
                fputcsv($file, ['商品編號', '商品名稱', '分類', '銷售數量', '營收', '成本', '毛利', '毛利率(%)']);

                // Data
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row['sku'],
                        $row['name'],
                        $row['category_name'],
                        $row['total_quantity'],
                        $row['total_revenue'],
                        $row['total_cost'],
                        $row['gross_profit'],
                        $row['profit_margin'],
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return $this->serverError('匯出利潤報表失敗：'.$e->getMessage());
        }
    }
}
