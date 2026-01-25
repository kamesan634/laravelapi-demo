<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * 權限資料填充
 */
class PermissionSeeder extends Seeder
{
    /**
     * 執行資料填充
     */
    public function run(): void
    {
        $permissions = [
            // 基礎資料模組
            ['name' => 'stores.view', 'display_name' => '檢視門市', 'module' => '基礎資料'],
            ['name' => 'stores.create', 'display_name' => '新增門市', 'module' => '基礎資料'],
            ['name' => 'stores.update', 'display_name' => '編輯門市', 'module' => '基礎資料'],
            ['name' => 'stores.delete', 'display_name' => '刪除門市', 'module' => '基礎資料'],

            ['name' => 'warehouses.view', 'display_name' => '檢視倉庫', 'module' => '基礎資料'],
            ['name' => 'warehouses.create', 'display_name' => '新增倉庫', 'module' => '基礎資料'],
            ['name' => 'warehouses.update', 'display_name' => '編輯倉庫', 'module' => '基礎資料'],
            ['name' => 'warehouses.delete', 'display_name' => '刪除倉庫', 'module' => '基礎資料'],

            ['name' => 'categories.view', 'display_name' => '檢視分類', 'module' => '基礎資料'],
            ['name' => 'categories.create', 'display_name' => '新增分類', 'module' => '基礎資料'],
            ['name' => 'categories.update', 'display_name' => '編輯分類', 'module' => '基礎資料'],
            ['name' => 'categories.delete', 'display_name' => '刪除分類', 'module' => '基礎資料'],

            ['name' => 'products.view', 'display_name' => '檢視商品', 'module' => '基礎資料'],
            ['name' => 'products.create', 'display_name' => '新增商品', 'module' => '基礎資料'],
            ['name' => 'products.update', 'display_name' => '編輯商品', 'module' => '基礎資料'],
            ['name' => 'products.delete', 'display_name' => '刪除商品', 'module' => '基礎資料'],

            ['name' => 'suppliers.view', 'display_name' => '檢視供應商', 'module' => '基礎資料'],
            ['name' => 'suppliers.create', 'display_name' => '新增供應商', 'module' => '基礎資料'],
            ['name' => 'suppliers.update', 'display_name' => '編輯供應商', 'module' => '基礎資料'],
            ['name' => 'suppliers.delete', 'display_name' => '刪除供應商', 'module' => '基礎資料'],

            ['name' => 'customers.view', 'display_name' => '檢視客戶', 'module' => '基礎資料'],
            ['name' => 'customers.create', 'display_name' => '新增客戶', 'module' => '基礎資料'],
            ['name' => 'customers.update', 'display_name' => '編輯客戶', 'module' => '基礎資料'],
            ['name' => 'customers.delete', 'display_name' => '刪除客戶', 'module' => '基礎資料'],

            // 銷售模組
            ['name' => 'orders.view', 'display_name' => '檢視訂單', 'module' => '銷售'],
            ['name' => 'orders.create', 'display_name' => '新增訂單', 'module' => '銷售'],
            ['name' => 'orders.update', 'display_name' => '編輯訂單', 'module' => '銷售'],
            ['name' => 'orders.cancel', 'display_name' => '取消訂單', 'module' => '銷售'],

            ['name' => 'payments.view', 'display_name' => '檢視付款', 'module' => '銷售'],
            ['name' => 'payments.create', 'display_name' => '新增付款', 'module' => '銷售'],

            ['name' => 'refunds.view', 'display_name' => '檢視退貨', 'module' => '銷售'],
            ['name' => 'refunds.create', 'display_name' => '新增退貨', 'module' => '銷售'],
            ['name' => 'refunds.approve', 'display_name' => '審核退貨', 'module' => '銷售'],

            ['name' => 'promotions.view', 'display_name' => '檢視促銷', 'module' => '銷售'],
            ['name' => 'promotions.create', 'display_name' => '新增促銷', 'module' => '銷售'],
            ['name' => 'promotions.update', 'display_name' => '編輯促銷', 'module' => '銷售'],
            ['name' => 'promotions.delete', 'display_name' => '刪除促銷', 'module' => '銷售'],

            // 庫存模組
            ['name' => 'inventory.view', 'display_name' => '檢視庫存', 'module' => '庫存'],
            ['name' => 'inventory.adjust', 'display_name' => '調整庫存', 'module' => '庫存'],

            ['name' => 'goods_receipts.view', 'display_name' => '檢視入庫單', 'module' => '庫存'],
            ['name' => 'goods_receipts.create', 'display_name' => '新增入庫單', 'module' => '庫存'],
            ['name' => 'goods_receipts.confirm', 'display_name' => '確認入庫', 'module' => '庫存'],

            ['name' => 'goods_issues.view', 'display_name' => '檢視出庫單', 'module' => '庫存'],
            ['name' => 'goods_issues.create', 'display_name' => '新增出庫單', 'module' => '庫存'],
            ['name' => 'goods_issues.confirm', 'display_name' => '確認出庫', 'module' => '庫存'],

            ['name' => 'stock_counts.view', 'display_name' => '檢視盤點', 'module' => '庫存'],
            ['name' => 'stock_counts.create', 'display_name' => '新增盤點', 'module' => '庫存'],
            ['name' => 'stock_counts.complete', 'display_name' => '完成盤點', 'module' => '庫存'],

            ['name' => 'stock_transfers.view', 'display_name' => '檢視調撥', 'module' => '庫存'],
            ['name' => 'stock_transfers.create', 'display_name' => '新增調撥', 'module' => '庫存'],
            ['name' => 'stock_transfers.approve', 'display_name' => '審核調撥', 'module' => '庫存'],

            // 採購模組
            ['name' => 'purchase_orders.view', 'display_name' => '檢視採購單', 'module' => '採購'],
            ['name' => 'purchase_orders.create', 'display_name' => '新增採購單', 'module' => '採購'],
            ['name' => 'purchase_orders.update', 'display_name' => '編輯採購單', 'module' => '採購'],
            ['name' => 'purchase_orders.approve', 'display_name' => '審核採購單', 'module' => '採購'],
            ['name' => 'purchase_orders.cancel', 'display_name' => '取消採購單', 'module' => '採購'],

            ['name' => 'purchase_receipts.view', 'display_name' => '檢視收貨單', 'module' => '採購'],
            ['name' => 'purchase_receipts.create', 'display_name' => '新增收貨單', 'module' => '採購'],
            ['name' => 'purchase_receipts.confirm', 'display_name' => '確認收貨', 'module' => '採購'],

            ['name' => 'purchase_returns.view', 'display_name' => '檢視採購退貨', 'module' => '採購'],
            ['name' => 'purchase_returns.create', 'display_name' => '新增採購退貨', 'module' => '採購'],
            ['name' => 'purchase_returns.approve', 'display_name' => '審核採購退貨', 'module' => '採購'],

            // 報表模組
            ['name' => 'reports.dashboard', 'display_name' => '檢視儀表板', 'module' => '報表'],
            ['name' => 'reports.sales', 'display_name' => '檢視銷售報表', 'module' => '報表'],
            ['name' => 'reports.inventory', 'display_name' => '檢視庫存報表', 'module' => '報表'],
            ['name' => 'reports.purchase', 'display_name' => '檢視採購報表', 'module' => '報表'],
            ['name' => 'reports.profit', 'display_name' => '檢視利潤報表', 'module' => '報表'],
            ['name' => 'reports.customer', 'display_name' => '檢視客戶報表', 'module' => '報表'],
            ['name' => 'reports.export', 'display_name' => '匯出報表', 'module' => '報表'],

            // 系統管理
            ['name' => 'roles.view', 'display_name' => '檢視角色', 'module' => '系統管理'],
            ['name' => 'roles.create', 'display_name' => '新增角色', 'module' => '系統管理'],
            ['name' => 'roles.update', 'display_name' => '編輯角色', 'module' => '系統管理'],
            ['name' => 'roles.delete', 'display_name' => '刪除角色', 'module' => '系統管理'],

            ['name' => 'permissions.view', 'display_name' => '檢視權限', 'module' => '系統管理'],

            ['name' => 'system_settings.view', 'display_name' => '檢視系統設定', 'module' => '系統管理'],
            ['name' => 'system_settings.update', 'display_name' => '編輯系統設定', 'module' => '系統管理'],

            ['name' => 'audit_logs.view', 'display_name' => '檢視操作日誌', 'module' => '系統管理'],

            ['name' => 'users.view', 'display_name' => '檢視使用者', 'module' => '系統管理'],
            ['name' => 'users.create', 'display_name' => '新增使用者', 'module' => '系統管理'],
            ['name' => 'users.update', 'display_name' => '編輯使用者', 'module' => '系統管理'],
            ['name' => 'users.delete', 'display_name' => '刪除使用者', 'module' => '系統管理'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}
