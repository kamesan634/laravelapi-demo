<?php

namespace App\Services\Reports;

use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 採購報表服務
 *
 * 提供採購相關的報表數據
 */
class PurchaseReportService
{
    /**
     * 取得採購報表
     */
    public function getPurchaseReport(array $filters): array
    {
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::now()->startOfMonth();
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now();
        $supplierId = $filters['supplier_id'] ?? null;

        $query = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate]);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $summary = $query->selectRaw('
            COUNT(*) as total_orders,
            SUM(CASE WHEN status IN ("APPROVED", "RECEIVED", "PARTIAL") THEN 1 ELSE 0 END) as approved_orders,
            SUM(subtotal) as gross_amount,
            SUM(tax_amount) as total_tax,
            SUM(total_amount) as total_amount
        ')->first();

        $byStatus = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->selectRaw('status, COUNT(*) as count, SUM(total_amount) as amount')
            ->groupBy('status')
            ->get();

        $dailyPurchases = $this->getDailyPurchases($startDate, $endDate, $supplierId);

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_orders' => $summary->total_orders ?? 0,
                'approved_orders' => $summary->approved_orders ?? 0,
                'gross_amount' => (float) ($summary->gross_amount ?? 0),
                'total_tax' => (float) ($summary->total_tax ?? 0),
                'total_amount' => (float) ($summary->total_amount ?? 0),
            ],
            'by_status' => $byStatus->map(function ($item) {
                return [
                    'status' => $item->status,
                    'count' => (int) $item->count,
                    'amount' => (float) $item->amount,
                ];
            })->toArray(),
            'daily_purchases' => $dailyPurchases,
        ];
    }

    /**
     * 供應商採購分析
     */
    public function getPurchaseBySupplier(array $filters): array
    {
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::now()->startOfMonth();
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now();

        $suppliers = DB::table('purchase_orders')
            ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->whereBetween('purchase_orders.order_date', [$startDate, $endDate])
            ->whereIn('purchase_orders.status', ['APPROVED', 'RECEIVED', 'PARTIAL'])
            ->selectRaw('
                suppliers.id,
                suppliers.code,
                suppliers.name,
                COUNT(DISTINCT purchase_orders.id) as order_count,
                SUM(purchase_orders.total_amount) as total_amount,
                AVG(purchase_orders.total_amount) as average_order_value
            ')
            ->groupBy('suppliers.id', 'suppliers.code', 'suppliers.name')
            ->orderByDesc('total_amount')
            ->get();

        $totalAmount = $suppliers->sum('total_amount');

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'total_amount' => (float) $totalAmount,
            'suppliers' => $suppliers->map(function ($item) use ($totalAmount) {
                return [
                    'supplier_id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'order_count' => (int) $item->order_count,
                    'total_amount' => (float) $item->total_amount,
                    'average_order_value' => round($item->average_order_value, 2),
                    'percentage' => $totalAmount > 0
                        ? round(($item->total_amount / $totalAmount) * 100, 2)
                        : 0,
                ];
            })->toArray(),
        ];
    }

    /**
     * 商品採購統計
     */
    public function getPurchaseByProduct(array $filters): array
    {
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::now()->startOfMonth();
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now();
        $supplierId = $filters['supplier_id'] ?? null;
        $limit = $filters['limit'] ?? 50;

        $query = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_order_items.po_id', '=', 'purchase_orders.id')
            ->join('products', 'purchase_order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('purchase_orders.order_date', [$startDate, $endDate])
            ->whereIn('purchase_orders.status', ['APPROVED', 'RECEIVED', 'PARTIAL']);

        if ($supplierId) {
            $query->where('purchase_orders.supplier_id', $supplierId);
        }

        $products = $query->selectRaw('
            products.id,
            products.sku,
            products.name,
            categories.name as category_name,
            SUM(purchase_order_items.quantity) as total_quantity,
            SUM(purchase_order_items.received_quantity) as received_quantity,
            SUM(purchase_order_items.subtotal) as total_amount,
            AVG(purchase_order_items.unit_price) as average_price,
            COUNT(DISTINCT purchase_orders.id) as order_count
        ')
            ->groupBy('products.id', 'products.sku', 'products.name', 'categories.name')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get();

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'products' => $products->map(function ($item) {
                return [
                    'product_id' => $item->id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'category_name' => $item->category_name,
                    'total_quantity' => (int) $item->total_quantity,
                    'received_quantity' => (int) $item->received_quantity,
                    'total_amount' => (float) $item->total_amount,
                    'average_price' => round($item->average_price, 2),
                    'order_count' => (int) $item->order_count,
                ];
            })->toArray(),
        ];
    }

    /**
     * 取得每日採購數據
     */
    protected function getDailyPurchases(Carbon $startDate, Carbon $endDate, ?int $supplierId = null): array
    {
        $purchases = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->selectRaw('
                DATE(order_date) as date,
                COUNT(*) as order_count,
                SUM(total_amount) as total_amount
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $dailyData = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $item = $purchases->get($dateKey);
            $dailyData[] = [
                'date' => $dateKey,
                'order_count' => $item ? (int) $item->order_count : 0,
                'total_amount' => $item ? (float) $item->total_amount : 0,
            ];
            $current->addDay();
        }

        return $dailyData;
    }

    /**
     * 取得匯出用的採購數據
     */
    public function getExportData(array $filters): array
    {
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::now()->startOfMonth();
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now();

        return PurchaseOrder::with(['supplier', 'warehouse', 'items.product'])
            ->whereBetween('order_date', [$startDate, $endDate])
            ->orderBy('order_date', 'desc')
            ->get()
            ->map(function ($po) {
                return [
                    'po_no' => $po->po_no,
                    'order_date' => $po->order_date->format('Y-m-d'),
                    'expected_date' => $po->expected_date?->format('Y-m-d') ?? '',
                    'supplier_name' => $po->supplier?->name ?? '',
                    'warehouse_name' => $po->warehouse?->name ?? '',
                    'subtotal' => $po->subtotal,
                    'tax_amount' => $po->tax_amount,
                    'total_amount' => $po->total_amount,
                    'status' => $po->status,
                ];
            })
            ->toArray();
    }
}
