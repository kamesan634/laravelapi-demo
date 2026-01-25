<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 採購建議服務
 *
 * 根據庫存和銷售數據產生採購建議
 */
class PurchaseSuggestionService
{
    /**
     * 取得採購建議列表
     */
    public function getSuggestions(array $filters): array
    {
        $warehouseId = $filters['warehouse_id'] ?? null;
        $categoryId = $filters['category_id'] ?? null;
        $supplierId = $filters['supplier_id'] ?? null;

        // 取得需要補貨的商品
        $query = DB::table('inventory')
            ->join('products', 'inventory.product_id', '=', 'products.id')
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

        if ($supplierId) {
            $query->where('products.supplier_id', $supplierId);
        }

        $products = $query->select([
            'products.id as product_id',
            'products.sku',
            'products.name as product_name',
            'products.cost_price',
            'products.safety_stock',
            'products.max_stock',
            'products.supplier_id',
            'categories.name as category_name',
            'suppliers.name as supplier_name',
            'inventory.warehouse_id',
            'inventory.quantity as current_quantity',
        ])
            ->orderBy('inventory.quantity')
            ->get();

        // 計算建議採購數量
        $suggestions = $products->map(function ($product) {
            $suggestedQuantity = max(0, $product->max_stock - $product->current_quantity);

            // 取得最近30天的銷售數據來調整建議數量
            $avgDailySales = $this->getAverageDailySales($product->product_id, 30);
            $leadTime = 7; // 預設採購前置時間（天）

            // 考慮前置時間的安全庫存
            $safetyBuffer = ceil($avgDailySales * $leadTime);
            if ($suggestedQuantity < $safetyBuffer) {
                $suggestedQuantity = $safetyBuffer;
            }

            return [
                'product_id' => $product->product_id,
                'sku' => $product->sku,
                'product_name' => $product->product_name,
                'category_name' => $product->category_name,
                'supplier_id' => $product->supplier_id,
                'supplier_name' => $product->supplier_name,
                'warehouse_id' => $product->warehouse_id,
                'current_quantity' => (int) $product->current_quantity,
                'safety_stock' => (int) $product->safety_stock,
                'max_stock' => (int) $product->max_stock,
                'suggested_quantity' => (int) $suggestedQuantity,
                'avg_daily_sales' => round($avgDailySales, 2),
                'unit_cost' => (float) $product->cost_price,
                'estimated_cost' => round($suggestedQuantity * $product->cost_price, 2),
                'urgency' => $this->calculateUrgency($product->current_quantity, $product->safety_stock),
            ];
        });

        // 按緊急程度排序
        $sorted = $suggestions->sortByDesc('urgency')->values();

        return [
            'total_items' => $sorted->count(),
            'total_estimated_cost' => $sorted->sum('estimated_cost'),
            'suggestions' => $sorted->toArray(),
        ];
    }

    /**
     * 產生採購建議（智能分析）
     */
    public function generateSuggestions(array $filters): array
    {
        $days = $filters['analysis_days'] ?? 30;
        $warehouseId = $filters['warehouse_id'] ?? null;

        // 分析銷售趨勢
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays($days);

        // 取得銷售數據
        $salesData = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.order_date', [$startDate, $endDate])
            ->whereIn('orders.status', ['COMPLETED', 'PAID'])
            ->where('products.track_inventory', true)
            ->selectRaw('
                order_items.product_id,
                SUM(order_items.quantity) as total_sold,
                COUNT(DISTINCT orders.id) as order_count,
                AVG(order_items.quantity) as avg_quantity_per_order
            ')
            ->groupBy('order_items.product_id')
            ->get()
            ->keyBy('product_id');

        // 取得庫存數據
        $inventoryQuery = DB::table('inventory')
            ->join('products', 'inventory.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->where('products.status', 'ACTIVE')
            ->where('products.track_inventory', true);

        if ($warehouseId) {
            $inventoryQuery->where('inventory.warehouse_id', $warehouseId);
        }

        $inventory = $inventoryQuery->select([
            'products.id as product_id',
            'products.sku',
            'products.name as product_name',
            'products.cost_price',
            'products.safety_stock',
            'products.max_stock',
            'products.supplier_id',
            'categories.name as category_name',
            'suppliers.name as supplier_name',
            'inventory.warehouse_id',
            'inventory.quantity as current_quantity',
        ])->get();

        // 產生智能建議
        $suggestions = $inventory->map(function ($product) use ($salesData, $days) {
            $sales = $salesData->get($product->product_id);
            $totalSold = $sales?->total_sold ?? 0;
            $avgDailySales = $days > 0 ? $totalSold / $days : 0;

            // 計算庫存天數
            $daysOfStock = $avgDailySales > 0
                ? floor($product->current_quantity / $avgDailySales)
                : 999;

            // 計算建議採購數量
            $leadTime = 7; // 採購前置時間
            $reorderPoint = ceil($avgDailySales * ($leadTime + 7)); // 前置時間 + 一週緩衝
            $suggestedQuantity = 0;

            if ($product->current_quantity <= $reorderPoint) {
                // 補貨到最大庫存
                $suggestedQuantity = max(0, $product->max_stock - $product->current_quantity);

                // 至少要補充到安全庫存以上
                $minRestock = max(0, $reorderPoint - $product->current_quantity);
                $suggestedQuantity = max($suggestedQuantity, $minRestock);
            }

            return [
                'product_id' => $product->product_id,
                'sku' => $product->sku,
                'product_name' => $product->product_name,
                'category_name' => $product->category_name,
                'supplier_id' => $product->supplier_id,
                'supplier_name' => $product->supplier_name,
                'warehouse_id' => $product->warehouse_id,
                'current_quantity' => (int) $product->current_quantity,
                'safety_stock' => (int) $product->safety_stock,
                'max_stock' => (int) $product->max_stock,
                'total_sold' => (int) $totalSold,
                'avg_daily_sales' => round($avgDailySales, 2),
                'days_of_stock' => min(999, $daysOfStock),
                'reorder_point' => (int) $reorderPoint,
                'suggested_quantity' => (int) $suggestedQuantity,
                'unit_cost' => (float) $product->cost_price,
                'estimated_cost' => round($suggestedQuantity * $product->cost_price, 2),
                'needs_restock' => $suggestedQuantity > 0,
            ];
        })
            ->filter(fn ($item) => $item['needs_restock'])
            ->sortBy('days_of_stock')
            ->values();

        return [
            'analysis_period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'days' => $days,
            ],
            'total_items' => $suggestions->count(),
            'total_estimated_cost' => $suggestions->sum('estimated_cost'),
            'suggestions' => $suggestions->toArray(),
        ];
    }

    /**
     * 將建議轉換為採購單
     */
    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $supplierId = $data['supplier_id'];
            $warehouseId = $data['warehouse_id'];
            $items = $data['items'];

            // 計算總金額
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            // 建立採購單
            $poNo = 'PO'.date('YmdHis').str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => $poNo,
                'supplier_id' => $supplierId,
                'order_date' => now(),
                'expected_date' => now()->addDays(7),
                'warehouse_id' => $warehouseId,
                'status' => 'DRAFT',
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'total_amount' => $subtotal,
                'notes' => $data['notes'] ?? '由採購建議自動產生',
                'created_by' => auth()->id(),
            ]);

            // 建立採購單明細
            foreach ($items as $item) {
                PurchaseOrderItem::create([
                    'po_id' => $purchaseOrder->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            $purchaseOrder->load(['supplier', 'warehouse', 'items.product']);

            return $purchaseOrder;
        });
    }

    /**
     * 取得平均每日銷售量
     */
    protected function getAverageDailySales(int $productId, int $days): float
    {
        $startDate = Carbon::now()->subDays($days);

        $totalSold = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $productId)
            ->where('orders.order_date', '>=', $startDate)
            ->whereIn('orders.status', ['COMPLETED', 'PAID'])
            ->sum('order_items.quantity');

        return $days > 0 ? $totalSold / $days : 0;
    }

    /**
     * 計算緊急程度 (1-5)
     */
    protected function calculateUrgency(int $currentQuantity, int $safetyStock): int
    {
        if ($currentQuantity <= 0) {
            return 5; // 已缺貨，最緊急
        }

        if ($safetyStock <= 0) {
            return 1;
        }

        $ratio = $currentQuantity / $safetyStock;

        if ($ratio <= 0.25) {
            return 4;
        }
        if ($ratio <= 0.5) {
            return 3;
        }
        if ($ratio <= 0.75) {
            return 2;
        }

        return 1;
    }
}
