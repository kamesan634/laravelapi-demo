<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\InventoryReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 庫存報表控制器
 *
 * 提供庫存報表相關的 API 端點
 */
class InventoryReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected InventoryReportService $inventoryReportService
    ) {}

    /**
     * 取得庫存報表
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->inventoryReportService->getInventoryReport($request->all());

            return $this->success($data, '取得庫存報表成功');
        } catch (\Exception $e) {
            return $this->serverError('取得庫存報表失敗：'.$e->getMessage());
        }
    }

    /**
     * 庫存價值分析
     */
    public function valuation(Request $request): JsonResponse
    {
        try {
            $data = $this->inventoryReportService->getValuation($request->all());

            return $this->success($data, '取得庫存價值分析成功');
        } catch (\Exception $e) {
            return $this->serverError('取得庫存價值分析失敗：'.$e->getMessage());
        }
    }

    /**
     * 庫存週轉率分析
     */
    public function turnover(Request $request): JsonResponse
    {
        try {
            $data = $this->inventoryReportService->getTurnover($request->all());

            return $this->success($data, '取得庫存週轉率分析成功');
        } catch (\Exception $e) {
            return $this->serverError('取得庫存週轉率分析失敗：'.$e->getMessage());
        }
    }

    /**
     * 低庫存預警
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $data = $this->inventoryReportService->getLowStock($request->all());

            return $this->success($data, '取得低庫存預警成功');
        } catch (\Exception $e) {
            return $this->serverError('取得低庫存預警失敗：'.$e->getMessage());
        }
    }

    /**
     * 匯出庫存報表
     */
    public function export(Request $request): Response|JsonResponse
    {
        try {
            $data = $this->inventoryReportService->getExportData($request->all());

            $filename = 'inventory_report_'.date('Y-m-d_His').'.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($data) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

                // Header
                fputcsv($file, ['商品編號', '商品名稱', '分類', '倉庫', '數量', '保留數量', '可用數量', '成本價', '庫存價值', '安全庫存', '低庫存']);

                // Data
                foreach ($data as $row) {
                    fputcsv($file, [
                        $row['sku'],
                        $row['product_name'],
                        $row['category_name'],
                        $row['warehouse_name'],
                        $row['quantity'],
                        $row['reserved_quantity'],
                        $row['available_quantity'],
                        $row['cost_price'],
                        $row['inventory_value'],
                        $row['safety_stock'],
                        $row['is_low_stock'] ? '是' : '否',
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return $this->serverError('匯出庫存報表失敗：'.$e->getMessage());
        }
    }
}
