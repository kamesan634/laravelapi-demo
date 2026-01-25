<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 庫存報表服務
 *
 * 提供庫存相關的報表數據
 */
class InventoryReportService
{
    /**
     * 取得庫存報表
     */
    public function getInventoryReport(array $filters): array
    {
        $warehouseId = $filters['warehouse_id'] ?? null;
        $categoryId = $filters['category_id'] ?? null;

        $query = DB::table('inventory')
            ->join('products', 'inventory.product_id', '=', 'products.id')
            ->join('warehouses', 'inventory.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.status', 'ACTIVE');

        if ($warehouseId) {
            $query->where('inventory.warehouse_id', $warehouseId);
        }

        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        }

        $inventory = $query->select([
            'inventory.id',
            'products.id as product_id',
            'products.sku',
            'products.name as product_name',
            'categories.name as category_name',
            'warehouses.name as warehouse_name',
            'inventory.quantity',
            'inventory.reserved_quantity',
            'products.cost_price',
            'products.safety_stock',
            'inventory.last_movement_date',
        ])
            ->orderBy('products.sku')
            ->get();

        $summary = [
            'total_products' => $inventory->unique('product_id')->count(),
            'total_quantity' => $inventory->sum('quantity'),
            'total_value' => $inventory->sum(function ($item) {
                return $item->quantity * $item->cost_price;
            }),
        ];

        return [
            'summary' => $summary,
            'inventory' => $inventory->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'sku' => $item->sku,
                    'product_name' => $item->product_name,
                    'category_name' => $item->category_name,
                    'warehouse_name' => $item->warehouse_name,
                    'quantity' => (int) $item->quantity,
                    'reserved_quantity' => (int) $item->reserved_quantity,
                    'available_quantity' => (int) ($item->quantity - $item->reserved_quantity),
                    'cost_price' => (float) $item->cost_price,
                    'inventory_value' => (float) ($item->quantity * $item->cost_price),
                    'safety_stock' => (int) $item->safety_stock,
                    'is_low_stock' => $item->quantity <= $item->safety_stock,
                    'last_movement_date' => $item->last_movement_date,
                ];
            })->toArray(),
        ];
    }

    /**
     * 庫存價值分析
     */
    public function getValuation(array $filters): array
    {
        $warehouseId = $filters['warehouse_id'] ?? null;

        $query = DB::table('inventory')
            ->join('products', 'inventory.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.status', 'ACTIVE');

        if ($warehouseId) {
            $query->where('inventory.warehouse_id', $warehouseId);
        }

        // 按分類統計
        $byCategory = $query->clone()
            ->selectRaw('
                categories.id,
                categories.name,
                COUNT(DISTINCT products.id) as product_count,
                SUM(inventory.quantity) as total_quantity,
                SUM(inventory.quantity * products.cost_price) as total_value
            ')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_value')
            ->get();

        // 總計
        $total = [
            'total_products' => $byCategory->sum('product_count'),
            'total_quantity' => $byCategory->sum('total_quantity'),
            'total_value' => (float) $byCategory->sum('total_value'),
        ];

        // 按倉庫統計
        $byWarehouse = DB::table('inventory')
            ->join('products', 'inventory.product_id', '=', 'products.id')
            ->join('warehouses', 'inventory.warehouse_id', '=', 'warehouses.id')
            ->where('products.status', 'ACTIVE')
            ->selectRaw('
                warehouses.id,
                warehouses.name,
                COUNT(DISTINCT inventory.product_id) as product_count,
                SUM(inventory.quantity) as total_quantity,
                SUM(inventory.quantity * products.cost_price) as total_value
            ')
            ->groupBy('warehouses.id', 'warehouses.name')
            ->orderByDesc('total_value')
            ->get();

        return [
            'total' => $total,
            'by_category' => $byCategory->map(function ($item) use ($total) {
                return [
                    'category_id' => $item->id,
                    'name' => $item->name,
                    'product_count' => (int) $item->product_count,
                    'total_quantity' => (int) $item->total_quantity,
                    'total_value' => (float) $item->total_value,
                    'percentage' => $total['total_value'] > 0
                        ? round(($item->total_value / $total['total_value']) * 100, 2)
                        : 0,
                ];
            })->toArray(),
            'by_warehouse' => $byWarehouse->map(function ($item) use ($total) {
                return [
                    'warehouse_id' => $item->id,
                    'name' => $item->name,
                    'product_count' => (int) $item->product_count,
                    'total_quantity' => (int) $item->total_quantity,
                    'total_value' => (float) $item->total_value,
                    'percentage' => $total['total_value'] > 0
                        ? round(($item->total_value / $total['total_value']) * 100, 2)
                        : 0,
                ];
            })->toArray(),
        ];
    }

    /**
     * 庫存週轉率分析
     */
    public function getTurnover(array $filters): array
    {
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::now()->subMonths(3);
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now();
        $days = $startDate->diffInDays($endDate) + 1;

        // 計算銷售成本 (COGS)
        $cogs = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.order_date', [$startDate, $endDate])
            ->whereIn('orders.status', ['COMPLETED', 'PAID'])
            ->selectRaw('
                order_items.product_id,
                SUM(order_items.quantity * order_items.cost_price) as total_cogs,
                SUM(order_items.quantity) as total_sold
            ')
            ->groupBy('order_items.product_id')
            ->get()
            ->keyBy('product_id');

        // 取得平均庫存
        $averageInventory = DB::table('inventory')
            ->join('products', 'inventory.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.status', 'ACTIVE')
            ->selectRaw('
                inventory.product_id,
                products.sku,
                products.name,
                categories.name as category_name,
                products.cost_price,
                SUM(inventory.quantity) as current_quantity
            ')
            ->groupBy('inventory.product_id', 'products.sku', 'products.name', 'categories.name', 'products.cost_price')
            ->get();

        $turnoverData = $averageInventory->map(function ($item) use ($cogs, $days) {
            $productCogs = $cogs->get($item->product_id);
            $totalCogs = $productCogs?->total_cogs ?? 0;
            $totalSold = $productCogs?->total_sold ?? 0;

            $averageInventoryValue = $item->current_quantity * $item->cost_price;

            // 週轉率 = 銷售成本 / 平均庫存
            $turnoverRate = $averageInventoryValue > 0
                ? round($totalCogs / $averageInventoryValue, 2)
                : 0;

            // 週轉天數 = 期間天數 / 週轉率
            $turnoverDays = $turnoverRate > 0
                ? round($days / $turnoverRate)
                : null;

            return [
                'product_id' => $item->product_id,
                'sku' => $item->sku,
                'name' => $item->name,
                'category_name' => $item->category_name,
                'current_quantity' => (int) $item->current_quantity,
                'total_sold' => (int) $totalSold,
                'cogs' => (float) $totalCogs,
                'inventory_value' => (float) $averageInventoryValue,
                'turnover_rate' => $turnoverRate,
                'turnover_days' => $turnoverDays,
            ];
        });

        // 分類排序：週轉率高的在前
        $sorted = $turnoverData->sortByDesc('turnover_rate')->values();

        // 計算總體週轉率
        $totalCogs = $sorted->sum('cogs');
        $totalInventoryValue = $sorted->sum('inventory_value');
        $overallTurnoverRate = $totalInventoryValue > 0
            ? round($totalCogs / $totalInventoryValue, 2)
            : 0;

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'days' => $days,
            ],
            'summary' => [
                'total_cogs' => (float) $totalCogs,
                'total_inventory_value' => (float) $totalInventoryValue,
                'overall_turnover_rate' => $overallTurnoverRate,
                'overall_turnover_days' => $overallTurnoverRate > 0 ? round($days / $overallTurnoverRate) : null,
            ],
            'products' => $sorted->toArray(),
        ];
    }

    /**
     * 低庫存預警
     */
    public function getLowStock(array $filters): array
    {
        $warehouseId = $filters['warehouse_id'] ?? null;
        $categoryId = $filters['category_id'] ?? null;

        $query = DB::table('inventory')
            ->join('products', 'inventory.product_id', '=', 'products.id')
            ->join('warehouses', 'inventory.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->where('products.status', 'ACTIVE')
            ->where('products.track_inventory', true)
            ->whereRaw('inventory.quantity <= products.safety_stock');

        if ($warehouseId) {
            $query->where('inventory.warehouse_id', $warehouseId);
        }

        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        }

        $lowStock = $query->select([
            'products.id as product_id',
            'products.sku',
            'products.name as product_name',
            'categories.name as category_name',
            'warehouses.name as warehouse_name',
            'suppliers.name as supplier_name',
            'inventory.quantity',
            'products.safety_stock',
            'products.max_stock',
        ])
            ->orderBy('inventory.quantity')
            ->get();

        $outOfStock = $lowStock->filter(fn ($item) => $item->quantity <= 0)->count();
        $criticalStock = $lowStock->filter(fn ($item) => $item->quantity > 0 && $item->quantity <= $item->safety_stock * 0.5)->count();

        return [
            'summary' => [
                'total_low_stock' => $lowStock->count(),
                'out_of_stock' => $outOfStock,
                'critical_stock' => $criticalStock,
            ],
            'products' => $lowStock->map(function ($item) {
                $shortfall = $item->max_stock - $item->quantity;

                return [
                    'product_id' => $item->product_id,
                    'sku' => $item->sku,
                    'product_name' => $item->product_name,
                    'category_name' => $item->category_name,
                    'warehouse_name' => $item->warehouse_name,
                    'supplier_name' => $item->supplier_name,
                    'quantity' => (int) $item->quantity,
                    'safety_stock' => (int) $item->safety_stock,
                    'max_stock' => (int) $item->max_stock,
                    'shortfall' => (int) max(0, $shortfall),
                    'status' => $item->quantity <= 0 ? 'OUT_OF_STOCK' :
                              ($item->quantity <= $item->safety_stock * 0.5 ? 'CRITICAL' : 'LOW'),
                ];
            })->toArray(),
        ];
    }

    /**
     * 取得匯出用的庫存數據
     */
    public function getExportData(array $filters): array
    {
        $report = $this->getInventoryReport($filters);

        return $report['inventory'];
    }
}
