<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * 庫存資料 Seeder
 */
class InventorySeeder extends Seeder
{
    public function run(): void
    {
        // 取得所有啟用的商品和倉庫
        $products = Product::where('status', 'ACTIVE')->get();
        $warehouses = Warehouse::where('status', 'ACTIVE')
            ->where('type', 'WAREHOUSE')
            ->get();

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                // 根據商品類型設定不同的庫存量
                $baseQuantity = $this->getBaseQuantity($product->sku);

                // 主倉庫庫存較多
                $multiplier = $warehouse->is_default ? 2.0 : 1.0;

                // 加入一些隨機變化
                $quantity = (int) ($baseQuantity * $multiplier * (0.8 + (mt_rand(0, 40) / 100)));
                $reservedQuantity = (int) ($quantity * (mt_rand(0, 15) / 100));

                Inventory::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => $quantity,
                    'reserved_quantity' => $reservedQuantity,
                    'last_count_date' => now()->subDays(mt_rand(1, 30)),
                    'last_movement_date' => now()->subDays(mt_rand(0, 7)),
                ]);
            }
        }

        // 為門市倉庫建立較少的庫存
        $storeWarehouses = Warehouse::where('status', 'ACTIVE')
            ->where('type', 'STORE')
            ->get();

        // 只為部分熱門商品建立門市庫存
        $popularProducts = Product::where('status', 'ACTIVE')
            ->whereIn('sku', [
                'PHONE-001', 'PHONE-002', 'ACC-001', 'ACC-002',
                'SNACK-001', 'SNACK-002', 'SNACK-003',
                'DRINK-001', 'DRINK-002', 'DRINK-003',
                'CLEAN-001', 'CLEAN-002',
                'SKIN-003', 'MAKEUP-002', 'HAIR-002',
            ])
            ->get();

        foreach ($popularProducts as $product) {
            foreach ($storeWarehouses as $warehouse) {
                $baseQuantity = $this->getBaseQuantity($product->sku) * 0.3;
                $quantity = (int) ($baseQuantity * (0.5 + (mt_rand(0, 50) / 100)));
                $reservedQuantity = (int) ($quantity * (mt_rand(0, 10) / 100));

                Inventory::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => max(5, $quantity),
                    'reserved_quantity' => $reservedQuantity,
                    'last_count_date' => now()->subDays(mt_rand(1, 14)),
                    'last_movement_date' => now()->subDays(mt_rand(0, 3)),
                ]);
            }
        }
    }

    /**
     * 根據商品 SKU 決定基礎庫存量
     */
    private function getBaseQuantity(string $sku): int
    {
        // 手機類商品庫存較少
        if (str_starts_with($sku, 'PHONE-')) {
            return 20;
        }

        // 配件類商品庫存中等
        if (str_starts_with($sku, 'ACC-') || str_starts_with($sku, 'COMP-') || str_starts_with($sku, 'AUDIO-')) {
            return 50;
        }

        // 服飾類商品
        if (str_starts_with($sku, 'MEN-') || str_starts_with($sku, 'WOMEN-') || str_starts_with($sku, 'SHOES-') || str_starts_with($sku, 'BAG-')) {
            return 30;
        }

        // 食品飲料類庫存較多
        if (str_starts_with($sku, 'SNACK-') || str_starts_with($sku, 'DRINK-')) {
            return 200;
        }

        // 居家用品
        if (str_starts_with($sku, 'FURN-') || str_starts_with($sku, 'KITCH-') || str_starts_with($sku, 'CLEAN-')) {
            return 60;
        }

        // 美妝類商品
        if (str_starts_with($sku, 'SKIN-') || str_starts_with($sku, 'MAKEUP-') || str_starts_with($sku, 'HAIR-')) {
            return 40;
        }

        return 50;
    }
}
