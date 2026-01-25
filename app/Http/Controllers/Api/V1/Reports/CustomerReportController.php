<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\CustomerReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 客戶報表控制器
 *
 * 提供客戶報表相關的 API 端點
 */
class CustomerReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CustomerReportService $customerReportService
    ) {}

    /**
     * 取得客戶報表
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->customerReportService->getCustomerReport($request->all());

            return $this->success($data, '取得客戶報表成功');
        } catch (\Exception $e) {
            return $this->serverError('取得客戶報表失敗：'.$e->getMessage());
        }
    }

    /**
     * RFM 分析
     */
    public function rfm(Request $request): JsonResponse
    {
        try {
            $data = $this->customerReportService->getRfmAnalysis($request->all());

            return $this->success($data, '取得 RFM 分析成功');
        } catch (\Exception $e) {
            return $this->serverError('取得 RFM 分析失敗：'.$e->getMessage());
        }
    }

    /**
     * 客戶消費排行
     */
    public function ranking(Request $request): JsonResponse
    {
        try {
            $data = $this->customerReportService->getCustomerRanking($request->all());

            return $this->success($data, '取得客戶消費排行成功');
        } catch (\Exception $e) {
            return $this->serverError('取得客戶消費排行失敗：'.$e->getMessage());
        }
    }

    /**
     * 客戶留存分析
     */
    public function retention(Request $request): JsonResponse
    {
        try {
            $data = $this->customerReportService->getRetention($request->all());

            return $this->success($data, '取得客戶留存分析成功');
        } catch (\Exception $e) {
            return $this->serverError('取得客戶留存分析失敗：'.$e->getMessage());
        }
    }

    /**
     * 匯出客戶報表
     */
    public function export(Request $request): Response|JsonResponse
    {
        try {
            $data = $this->customerReportService->getExportData($request->all());

            $filename = 'customer_report_'.date('Y-m-d_His').'.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($data) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

                // Header
                fputcsv($file, ['會員編號', '姓名', '電話', '電子郵件', '會員等級', '累計消費', '累計點數', '可用點數', '加入日期', '狀態']);

                // Data
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row['member_no'],
                        $row['name'],
                        $row['phone'],
                        $row['email'],
                        $row['level_name'],
                        $row['total_spending'],
                        $row['total_points'],
                        $row['available_points'],
                        $row['join_date'],
                        $row['status'],
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return $this->serverError('匯出客戶報表失敗：'.$e->getMessage());
        }
    }
}
