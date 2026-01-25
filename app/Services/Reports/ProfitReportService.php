<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 利潤報表服務
 *
 * 提供利潤相關的報表數據
 */
class ProfitReportService
{
    /**
     * 取得利潤報表
     */
    public function getProfitReport(array $filters): array
    {
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::now()->startOfMonth();
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now();
        $storeId = $filters['store_id'] ?? null;

        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.order_date', [$startDate, $endDate])
            ->whereIn('orders.status', ['COMPLETED', 'PAID']);

        if ($storeId) {
            $query->where('orders.store_id', $storeId);
        }

        $summary = $query->selectRaw('
            SUM(order_items.subtotal) as total_revenue,
            SUM(order_items.quantity * order_items.cost_price) as total_cost,
            SUM(order_items.subtotal - (order_items.quantity * order_items.cost_price)) as gross_profit
        ')->first();

        $totalRevenue = (float) ($summary->total_revenue ?? 0);
        $totalCost = (float) ($summary->total_cost ?? 0);
        $grossProfit = (float) ($summary->gross_profit ?? 0);
        $profitMargin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 2) : 0;

        $dailyProfit = $this->getDailyProfit($startDate, $endDate, $storeId);

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_cost' => $totalCost,
                'gross_profit' => $grossProfit,
                'profit_margin' => $profitMargin,
            ],
            'daily_profit' => $dailyProfit,
        ];
    }

    /**
     * 商品利潤分析
     */
    public function getProfitByProduct(array $filters): array
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
            SUM(order_items.subtotal) as total_revenue,
            SUM(order_items.quantity * order_items.cost_price) as total_cost,
            SUM(order_items.subtotal - (order_items.quantity * order_items.cost_price)) as gross_profit
        ')
            ->groupBy('products.id', 'products.sku', 'products.name', 'categories.name')
            ->orderByDesc('gross_profit')
            ->limit($limit)
            ->get();

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'products' => $products->map(function ($item) {
                $profitMargin = $item->total_revenue > 0
                    ? round(($item->gross_profit / $item->total_revenue) * 100, 2)
                    : 0;

                return [
                    'product_id' => $item->id,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'category_name' => $item->category_name,
                    'total_quantity' => (int) $item->total_quantity,
                    'total_revenue' => (float) $item->total_revenue,
                    'total_cost' => (float) $item->total_cost,
                    'gross_profit' => (float) $item->gross_profit,
                    'profit_margin' => $profitMargin,
                ];
            })->toArray(),
        ];
    }

    /**
     * 分類利潤統計
     */
    public function getProfitByCategory(array $filters): array
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
                SUM(order_items.subtotal) as total_revenue,
                SUM(order_items.quantity * order_items.cost_price) as total_cost,
                SUM(order_items.subtotal - (order_items.quantity * order_items.cost_price)) as gross_profit,
                COUNT(DISTINCT products.id) as product_count
            ')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('gross_profit')
            ->get();

        $totalProfit = $categories->sum('gross_profit');

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'total_profit' => (float) $totalProfit,
            'categories' => $categories->map(function ($item) use ($totalProfit) {
                $profitMargin = $item->total_revenue > 0
                    ? round(($item->gross_profit / $item->total_revenue) * 100, 2)
                    : 0;

                return [
                    'category_id' => $item->id,
                    'name' => $item->name,
                    'total_revenue' => (float) $item->total_revenue,
                    'total_cost' => (float) $item->total_cost,
                    'gross_profit' => (float) $item->gross_profit,
                    'profit_margin' => $profitMargin,
                    'product_count' => (int) $item->product_count,
                    'profit_share' => $totalProfit > 0
                        ? round(($item->gross_profit / $totalProfit) * 100, 2)
                        : 0,
                ];
            })->toArray(),
        ];
    }

    /**
     * 毛利率分析
     */
    public function getMarginAnalysis(array $filters): array
    {
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::now()->startOfMonth();
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now();

        // 按毛利率區間統計
        $products = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.order_date', [$startDate, $endDate])
            ->whereIn('orders.status', ['COMPLETED', 'PAID'])
            ->selectRaw('
                products.id,
                products.sku,
                products.name,
                SUM(order_items.subtotal) as total_revenue,
                SUM(order_items.quantity * order_items.cost_price) as total_cost
            ')
            ->groupBy('products.id', 'products.sku', 'products.name')
            ->get()
            ->map(function ($item) {
                $profitMargin = $item->total_revenue > 0
                    ? (($item->total_revenue - $item->total_cost) / $item->total_revenue) * 100
                    : 0;
                $item->profit_margin = round($profitMargin, 2);

                return $item;
            });

        // 分組統計
        $ranges = [
            ['min' => -100, 'max' => 0, 'label' => '虧損 (<0%)'],
            ['min' => 0, 'max' => 10, 'label' => '低毛利 (0-10%)'],
            ['min' => 10, 'max' => 20, 'label' => '一般毛利 (10-20%)'],
            ['min' => 20, 'max' => 30, 'label' => '良好毛利 (20-30%)'],
            ['min' => 30, 'max' => 50, 'label' => '高毛利 (30-50%)'],
            ['min' => 50, 'max' => 100, 'label' => '超高毛利 (>50%)'],
        ];

        $distribution = [];
        foreach ($ranges as $range) {
            $inRange = $products->filter(function ($item) use ($range) {
                return $item->profit_margin >= $range['min'] && $item->profit_margin < $range['max'];
            });

            $distribution[] = [
                'range' => $range['label'],
                'product_count' => $inRange->count(),
                'total_revenue' => (float) $inRange->sum('total_revenue'),
            ];
        }

        // 找出毛利率最高和最低的商品
        $sorted = $products->sortByDesc('profit_margin');
        $topMargin = $sorted->take(10)->values()->map(function ($item) {
            return [
                'product_id' => $item->id,
                'sku' => $item->sku,
                'name' => $item->name,
                'profit_margin' => $item->profit_margin,
                'total_revenue' => (float) $item->total_revenue,
            ];
        })->toArray();

        $bottomMargin = $sorted->reverse()->take(10)->values()->map(function ($item) {
            return [
                'product_id' => $item->id,
                'sku' => $item->sku,
                'name' => $item->name,
                'profit_margin' => $item->profit_margin,
                'total_revenue' => (float) $item->total_revenue,
            ];
        })->toArray();

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'distribution' => $distribution,
            'top_margin_products' => $topMargin,
            'bottom_margin_products' => $bottomMargin,
        ];
    }

    /**
     * 取得每日利潤數據
     */
    protected function getDailyProfit(Carbon $startDate, Carbon $endDate, ?int $storeId = null): array
    {
        $profits = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.order_date', [$startDate, $endDate])
            ->whereIn('orders.status', ['COMPLETED', 'PAID'])
            ->when($storeId, fn ($q) => $q->where('orders.store_id', $storeId))
            ->selectRaw('
                DATE(orders.order_date) as date,
                SUM(order_items.subtotal) as revenue,
                SUM(order_items.quantity * order_items.cost_price) as cost
            ')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $dailyData = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $item = $profits->get($dateKey);
            $revenue = $item ? (float) $item->revenue : 0;
            $cost = $item ? (float) $item->cost : 0;
            $profit = $revenue - $cost;

            $dailyData[] = [
                'date' => $dateKey,
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => $profit,
                'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0,
            ];
            $current->addDay();
        }

        return $dailyData;
    }

    /**
     * 取得匯出用的利潤數據
     */
    public function getExportData(array $filters): array
    {
        $report = $this->getProfitByProduct($filters);

        return $report['products'];
    }
}
