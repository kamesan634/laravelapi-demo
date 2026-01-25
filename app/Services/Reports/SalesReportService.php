<?php

namespace App\Services\Reports;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 銷售報表服務
 *
 * 提供銷售相關的報表數據
 */
class SalesReportService
{
    /**
     * 取得銷售報表
     */
    public function getSalesReport(array $filters): array
    {
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::now()->startOfMonth();
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now();
        $storeId = $filters['store_id'] ?? null;

        $query = Order::whereBetween('order_date', [$startDate, $endDate])
            ->whereIn('status', ['COMPLETED', 'PAID']);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $summary = $query->selectRaw('
            COUNT(*) as total_orders,
            SUM(subtotal) as gross_sales,
            SUM(discount_amount) as total_discount,
            SUM(tax_amount) as total_tax,
            SUM(total_amount) as net_sales,
            AVG(total_amount) as average_order_value
        ')->first();

        $dailySales = $this->getDailySales($startDate, $endDate, $storeId);

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_orders' => $summary->total_orders ?? 0,
                'gross_sales' => (float) ($summary->gross_sales ?? 0),
                'total_discount' => (float) ($summary->total_discount ?? 0),
                'total_tax' => (float) ($summary->total_tax ?? 0),
                'net_sales' => (float) ($summary->net_sales ?? 0),
                'average_order_value' => round($summary->average_order_value ?? 0, 2),
            ],
            'daily_sales' => $dailySales,
        ];
    }

    /**
     * 商品銷售排行
     */
    public function getSalesByProduct(array $filters): array
    {
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::now()->startOfMonth();
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now();
        $categoryId = $filters['category_id'] ?? null;
        $limit = $filters['limit'] ?? 50;

        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('orders.order_date', [$startDate, $endDate])
            ->whereIn('orders.status', ['COMPLETED', 'PAID']);

        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        }

        $products = $query->selectRaw('
            products.id,
            products.sku,
            products.name,
            categories.name as category_name,
            SUM(order_items.quantity) as total_quantity,
            SUM(order_items.subtotal) as total_sales,
            COUNT(DISTINCT orders.id) as order_count
        ')
            ->groupBy('products.id', 'products.sku', 'products.name', 'categories.name')
            ->orderByDesc('total_sales')
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
                    'total_sales' => (float) $item->total_sales,
                    'order_count' => (int) $item->order_count,
                ];
            })->toArray(),
        ];
    }

    /**
     * 分類銷售統計
     */
    public function getSalesByCategory(array $filters): array
    {
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::now()->startOfMonth();
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now();

        $categories = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('orders.order_date', [$startDate, $endDate])
            ->whereIn('orders.status', ['COMPLETED', 'PAID'])
            ->selectRaw('
                categories.id,
                categories.name,
                SUM(order_items.quantity) as total_quantity,
                SUM(order_items.subtotal) as total_sales,
                COUNT(DISTINCT products.id) as product_count
            ')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_sales')
            ->get();

        $totalSales = $categories->sum('total_sales');

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'total_sales' => (float) $totalSales,
            'categories' => $categories->map(function ($item) use ($totalSales) {
                return [
                    'category_id' => $item->id,
                    'name' => $item->name,
                    'total_quantity' => (int) $item->total_quantity,
                    'total_sales' => (float) $item->total_sales,
                    'product_count' => (int) $item->product_count,
                    'percentage' => $totalSales > 0 ? round(($item->total_sales / $totalSales) * 100, 2) : 0,
                ];
            })->toArray(),
        ];
    }

    /**
     * 時段銷售分析
     */
    public function getSalesByTime(array $filters): array
    {
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::now()->startOfMonth();
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now();
        $groupBy = $filters['group_by'] ?? 'hour'; // hour, day_of_week, month

        $query = Order::whereBetween('order_date', [$startDate, $endDate])
            ->whereIn('status', ['COMPLETED', 'PAID']);

        switch ($groupBy) {
            case 'hour':
                $data = $query->selectRaw('
                    HOUR(order_date) as period,
                    COUNT(*) as order_count,
                    SUM(total_amount) as total_sales
                ')
                    ->groupBy('period')
                    ->orderBy('period')
                    ->get();

                $formatted = [];
                for ($i = 0; $i < 24; $i++) {
                    $item = $data->firstWhere('period', $i);
                    $formatted[] = [
                        'period' => sprintf('%02d:00', $i),
                        'order_count' => $item ? (int) $item->order_count : 0,
                        'total_sales' => $item ? (float) $item->total_sales : 0,
                    ];
                }
                break;

            case 'day_of_week':
                $data = $query->selectRaw('
                    DAYOFWEEK(order_date) as period,
                    COUNT(*) as order_count,
                    SUM(total_amount) as total_sales
                ')
                    ->groupBy('period')
                    ->orderBy('period')
                    ->get();

                $dayNames = ['', '週日', '週一', '週二', '週三', '週四', '週五', '週六'];
                $formatted = [];
                for ($i = 1; $i <= 7; $i++) {
                    $item = $data->firstWhere('period', $i);
                    $formatted[] = [
                        'period' => $dayNames[$i],
                        'order_count' => $item ? (int) $item->order_count : 0,
                        'total_sales' => $item ? (float) $item->total_sales : 0,
                    ];
                }
                break;

            case 'month':
                $data = $query->selectRaw('
                    DATE_FORMAT(order_date, "%Y-%m") as period,
                    COUNT(*) as order_count,
                    SUM(total_amount) as total_sales
                ')
                    ->groupBy('period')
                    ->orderBy('period')
                    ->get();

                $formatted = $data->map(function ($item) {
                    return [
                        'period' => $item->period,
                        'order_count' => (int) $item->order_count,
                        'total_sales' => (float) $item->total_sales,
                    ];
                })->toArray();
                break;

            default:
                $formatted = [];
        }

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'group_by' => $groupBy,
            'data' => $formatted,
        ];
    }

    /**
     * 取得每日銷售數據
     */
    protected function getDailySales(Carbon $startDate, Carbon $endDate, ?int $storeId = null): array
    {
        $query = Order::whereBetween('order_date', [$startDate, $endDate])
            ->whereIn('status', ['COMPLETED', 'PAID']);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $sales = $query->selectRaw('
            DATE(order_date) as date,
            COUNT(*) as order_count,
            SUM(total_amount) as total_sales
        ')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total_sales', 'date')
            ->toArray();

        $orderCounts = Order::whereBetween('order_date', [$startDate, $endDate])
            ->whereIn('status', ['COMPLETED', 'PAID'])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->selectRaw('DATE(order_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $dailyData = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $dailyData[] = [
                'date' => $dateKey,
                'order_count' => $orderCounts[$dateKey] ?? 0,
                'total_sales' => (float) ($sales[$dateKey] ?? 0),
            ];
            $current->addDay();
        }

        return $dailyData;
    }

    /**
     * 取得匯出用的銷售數據
     */
    public function getExportData(array $filters): array
    {
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::now()->startOfMonth();
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now();

        return Order::with(['customer', 'store', 'items.product'])
            ->whereBetween('order_date', [$startDate, $endDate])
            ->whereIn('status', ['COMPLETED', 'PAID'])
            ->orderBy('order_date', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'order_no' => $order->order_no,
                    'order_date' => $order->order_date->format('Y-m-d H:i:s'),
                    'store_name' => $order->store?->name ?? '',
                    'customer_name' => $order->customer?->name ?? '散客',
                    'subtotal' => $order->subtotal,
                    'discount_amount' => $order->discount_amount,
                    'tax_amount' => $order->tax_amount,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                ];
            })
            ->toArray();
    }
}
