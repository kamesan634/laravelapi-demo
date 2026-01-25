<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\SalesReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 銷售報表控制器
 *
 * 提供銷售報表相關的 API 端點
 */
class SalesReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SalesReportService $salesReportService
    ) {}

    /**
     * 取得銷售報表
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->salesReportService->getSalesReport($request->all());

            return $this->success($data, '取得銷售報表成功');
        } catch (\Exception $e) {
            return $this->serverError('取得銷售報表失敗：'.$e->getMessage());
        }
    }

    /**
     * 商品銷售排行
     */
    public function byProduct(Request $request): JsonResponse
    {
        try {
            $data = $this->salesReportService->getSalesByProduct($request->all());

            return $this->success($data, '取得商品銷售排行成功');
        } catch (\Exception $e) {
            return $this->serverError('取得商品銷售排行失敗：'.$e->getMessage());
        }
    }

    /**
     * 分類銷售統計
     */
    public function byCategory(Request $request): JsonResponse
    {
        try {
            $data = $this->salesReportService->getSalesByCategory($request->all());

            return $this->success($data, '取得分類銷售統計成功');
        } catch (\Exception $e) {
            return $this->serverError('取得分類銷售統計失敗：'.$e->getMessage());
        }
    }

    /**
     * 時段銷售分析
     */
    public function byTime(Request $request): JsonResponse
    {
        try {
            $data = $this->salesReportService->getSalesByTime($request->all());

            return $this->success($data, '取得時段銷售分析成功');
        } catch (\Exception $e) {
            return $this->serverError('取得時段銷售分析失敗：'.$e->getMessage());
        }
    }

    /**
     * 匯出銷售報表
     */
    public function export(Request $request): Response|JsonResponse
    {
        try {
            $data = $this->salesReportService->getExportData($request->all());

            $filename = 'sales_report_'.date('Y-m-d_His').'.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($data) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

                // Header
                fputcsv($file, ['訂單編號', '訂單日期', '門市', '客戶', '小計', '折扣', '稅額', '總金額', '狀態']);

                // Data
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row['order_no'],
                        $row['order_date'],
                        $row['store_name'],
                        $row['customer_name'],
                        $row['subtotal'],
                        $row['discount_amount'],
                        $row['tax_amount'],
                        $row['total_amount'],
                        $row['status'],
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return $this->serverError('匯出銷售報表失敗：'.$e->getMessage());
        }
    }
}
