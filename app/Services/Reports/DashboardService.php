<?php

namespace App\Services\Reports;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 儀表板報表服務
 *
 * 提供儀表板相關的統計數據
 */
class DashboardService
{
    /**
     * 取得儀表板總覽數據
     *
     * @param  string|null  $startDate  開始日期
     * @param  string|null  $endDate  結束日期
     */
    public function getOverview(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        return [
            'sales' => $this->getSalesOverview($startDate, $endDate),
            'orders' => $this->getOrdersOverview($startDate, $endDate),
            'customers' => $this->getCustomersOverview($startDate, $endDate),
            'inventory' => $this->getInventoryOverview(),
            'purchase' => $this->getPurchaseOverview($startDate, $endDate),
        ];
    }

    /**
     * 取得今日數據
     */
    public function getTodayData(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $todaySales = Order::whereDate('order_date', $today)
            ->whereIn('status', ['COMPLETED', 'PAID'])
            ->sum('total_amount');

        $yesterdaySales = Order::whereDate('order_date', $yesterday)
            ->whereIn('status', ['COMPLETED', 'PAID'])
            ->sum('total_amount');

        $todayOrders = Order::whereDate('order_date', $today)->count();
        $yesterdayOrders = Order::whereDate('order_date', $yesterday)->count();

        $todayCustomers = Customer::whereDate('created_at', $today)->count();

        return [
            'today_sales' => (float) $todaySales,
            'yesterday_sales' => (float) $yesterdaySales,
            'sales_growth' => $yesterdaySales > 0
                ? round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 2)
                : 0,
            'today_orders' => $todayOrders,
            'yesterday_orders' => $yesterdayOrders,
            'orders_growth' => $yesterdayOrders > 0
                ? round((($todayOrders - $yesterdayOrders) / $yesterdayOrders) * 100, 2)
                : 0,
            'today_new_customers' => $todayCustomers,
            'hourly_sales' => $this->getHourlySales($today),
        ];
    }

    /**
     * 取得趨勢數據
     *
     * @param  int  $days  天數
     */
    public function getTrends(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();

        return [
            'daily_sales' => $this->getDailySalesTrend($startDate, $endDate),
            'daily_orders' => $this->getDailyOrdersTrend($startDate, $endDate),
            'top_products' => $this->getTopProducts($startDate, $endDate, 10),
            'top_categories' => $this->getTopCategories($startDate, $endDate, 10),
        ];
    }

    /**
     * 取得銷售總覽
     */
    protected function getSalesOverview(Carbon $startDate, Carbon $endDate): array
    {
        $sales = Order::whereBetween('order_date', [$startDate, $endDate])
            ->whereIn('status', ['COMPLETED', 'PAID'])
            ->selectRaw('SUM(total_amount) as total_sales, SUM(discount_amount) as total_discount, COUNT(*) as order_count')
            ->first();

        return [
            'total_sales' => (float) ($sales->total_sales ?? 0),
            'total_discount' => (float) ($sales->total_discount ?? 0),
            'order_count' => $sales->order_count ?? 0,
            'average_order_value' => $sales->order_count > 0
                ? round($sales->total_sales / $sales->order_count, 2)
                : 0,
        ];
    }

    /**
     * 取得訂單總覽
     */
    protected function getOrdersOverview(Carbon $startDate, Carbon $endDate): array
    {
        $orders = Order::whereBetween('order_date', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => array_sum($orders),
            'pending' => $orders['PENDING'] ?? 0,
            'paid' => $orders['PAID'] ?? 0,
            'completed' => $orders['COMPLETED'] ?? 0,
            'cancelled' => $orders['CANCELLED'] ?? 0,
        ];
    }

    /**
     * 取得客戶總覽
     */
    protected function getCustomersOverview(Carbon $startDate, Carbon $endDate): array
    {
        $newCustomers = Customer::whereBetween('created_at', [$startDate, $endDate])->count();
        $totalCustomers = Customer::where('status', 'ACTIVE')->count();

        $activeCustomers = Order::whereBetween('order_date', [$startDate, $endDate])
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        return [
            'total_customers' => $totalCustomers,
            'new_customers' => $newCustomers,
            'active_customers' => $activeCustomers,
        ];
    }

    /**
     * 取得庫存總覽
     */
    protected function getInventoryOverview(): array
    {
        $totalProducts = Product::where('status', 'ACTIVE')->count();

        $lowStockProducts = DB::table('inventory')
            ->join('products', 'inventory.product_id', '=', 'products.id')
            ->whereRaw('inventory.quantity <= products.safety_stock')
            ->where('products.track_inventory', true)
            ->distinct('inventory.product_id')
            ->count('inventory.product_id');

        $outOfStockProducts = Inventory::where('quantity', '<=', 0)
            ->distinct('product_id')
            ->count('product_id');

        $totalInventoryValue = DB::table('inventory')
            ->join('products', 'inventory.product_id', '=', 'products.id')
            ->selectRaw('SUM(inventory.quantity * products.cost_price) as value')
            ->value('value');

        return [
            'total_products' => $totalProducts,
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
            'total_inventory_value' => (float) ($totalInventoryValue ?? 0),
        ];
    }

    /**
     * 取得採購總覽
     */
    protected function getPurchaseOverview(Carbon $startDate, Carbon $endDate): array
    {
        $purchases = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count, SUM(total_amount) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $totalAmount = $purchases->sum('amount');

        return [
            'total_amount' => (float) $totalAmount,
            'pending_count' => $purchases->get('DRAFT')?->count ?? 0,
            'approved_count' => $purchases->get('APPROVED')?->count ?? 0,
            'received_count' => $purchases->get('RECEIVED')?->count ?? 0,
        ];
    }

    /**
     * 取得每小時銷售額
     */
    protected function getHourlySales(Carbon $date): array
    {
        $sales = Order::whereDate('order_date', $date)
            ->whereIn('status', ['COMPLETED', 'PAID'])
            ->selectRaw('HOUR(order_date) as hour, SUM(total_amount) as amount')
            ->groupBy('hour')
            ->pluck('amount', 'hour')
            ->toArray();

        $hourlyData = [];
        for ($i = 0; $i < 24; $i++) {
            $hourlyData[] = [
                'hour' => sprintf('%02d:00', $i),
                'amount' => (float) ($sales[$i] ?? 0),
            ];
        }

        return $hourlyData;
    }

    /**
     * 取得每日銷售趨勢
     */
    protected function getDailySalesTrend(Carbon $startDate, Carbon $endDate): array
    {
        $sales = Order::whereBetween('order_date', [$startDate, $endDate])
            ->whereIn('status', ['COMPLETED', 'PAID'])
            ->selectRaw('DATE(order_date) as date, SUM(total_amount) as amount')
            ->groupBy('date')
            ->pluck('amount', 'date')
            ->toArray();

        $trend = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $trend[] = [
                'date' => $dateKey,
                'amount' => (float) ($sales[$dateKey] ?? 0),
            ];
            $current->addDay();
        }

        return $trend;
    }

    /**
     * 取得每日訂單趨勢
     */
    protected function getDailyOrdersTrend(Carbon $startDate, Carbon $endDate): array
    {
        $orders = Order::whereBetween('order_date', [$startDate, $endDate])
            ->selectRaw('DATE(order_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $trend = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $trend[] = [
                'date' => $dateKey,
                'count' => $orders[$dateKey] ?? 0,
            ];
            $current->addDay();
        }

        return $trend;
    }

    /**
     * 取得熱銷商品
     */
    protected function getTopProducts(Carbon $startDate, Carbon $endDate, int $limit = 10): array
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.order_date', [$startDate, $endDate])
            ->whereIn('orders.status', ['COMPLETED', 'PAID'])
            ->selectRaw('products.id, products.name, products.sku, SUM(order_items.quantity) as total_quantity, SUM(order_items.subtotal) as total_amount')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * 取得熱銷分類
     */
    protected function getTopCategories(Carbon $startDate, Carbon $endDate, int $limit = 10): array
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('orders.order_date', [$startDate, $endDate])
            ->whereIn('orders.status', ['COMPLETED', 'PAID'])
            ->selectRaw('categories.id, categories.name, SUM(order_items.quantity) as total_quantity, SUM(order_items.subtotal) as total_amount')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
