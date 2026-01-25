<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\PurchaseReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 採購報表控制器
 *
 * 提供採購報表相關的 API 端點
 */
class PurchaseReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PurchaseReportService $purchaseReportService
    ) {}

    /**
     * 取得採購報表
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->purchaseReportService->getPurchaseReport($request->all());

            return $this->success($data, '取得採購報表成功');
        } catch (\Exception $e) {
            return $this->serverError('取得採購報表失敗：'.$e->getMessage());
        }
    }

    /**
     * 供應商採購分析
     */
    public function bySupplier(Request $request): JsonResponse
    {
        try {
            $data = $this->purchaseReportService->getPurchaseBySupplier($request->all());

            return $this->success($data, '取得供應商採購分析成功');
        } catch (\Exception $e) {
            return $this->serverError('取得供應商採購分析失敗：'.$e->getMessage());
        }
    }

    /**
     * 商品採購統計
     */
    public function byProduct(Request $request): JsonResponse
    {
        try {
            $data = $this->purchaseReportService->getPurchaseByProduct($request->all());

            return $this->success($data, '取得商品採購統計成功');
        } catch (\Exception $e) {
            return $this->serverError('取得商品採購統計失敗：'.$e->getMessage());
        }
    }

    /**
     * 匯出採購報表
     */
    public function export(Request $request): Response|JsonResponse
    {
        try {
            $data = $this->purchaseReportService->getExportData($request->all());

            $filename = 'purchase_report_'.date('Y-m-d_His').'.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($data) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

                // Header
                fputcsv($file, ['採購單號', '訂單日期', '預計到貨日', '供應商', '倉庫', '小計', '稅額', '總金額', '狀態']);

                // Data
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row['po_no'],
                        $row['order_date'],
                        $row['expected_date'],
                        $row['supplier_name'],
                        $row['warehouse_name'],
                        $row['subtotal'],
                        $row['tax_amount'],
                        $row['total_amount'],
                        $row['status'],
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return $this->serverError('匯出採購報表失敗：'.$e->getMessage());
        }
    }
}
