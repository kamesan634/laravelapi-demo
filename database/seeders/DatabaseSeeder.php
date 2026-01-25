<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 資料庫 Seeder
 *
 * 執行順序依照相依性排列
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 建立測試使用者
        User::factory()->create([
            'name' => '系統管理員',
            'email' => 'admin@demo-pos.com',
        ]);

        User::factory()->create([
            'name' => '王大明',
            'email' => 'cashier@demo-pos.com',
        ]);

        // 執行 Seeders（依相依性順序）
        $this->call([
            // 1. 基礎資料（無相依性）
            StoreSeeder::class,
            CustomerLevelSeeder::class,
            CategorySeeder::class,
            SupplierSeeder::class,

            // 2. 相依基礎資料
            WarehouseSeeder::class,      // 依賴 Store
            CustomerSeeder::class,       // 依賴 CustomerLevel, Store
            ProductSeeder::class,        // 依賴 Category, Supplier

            // 3. 交易相關資料
            InventorySeeder::class,      // 依賴 Product, Warehouse
            OrderSeeder::class,          // 依賴 Store, Customer, Product, User

            // 4. 系統管理資料
            RoleSeeder::class,           // 角色資料
            PermissionSeeder::class,     // 權限資料
            SystemSettingSeeder::class,  // 系統設定
            NumberSequenceSeeder::class, // 編號規則
        ]);
    }
}
