<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

/**
 * 系統設定資料填充
 */
class SystemSettingSeeder extends Seeder
{
    /**
     * 執行資料填充
     */
    public function run(): void
    {
        $settings = [
            // 公司資訊
            [
                'key' => 'company_name',
                'value' => 'POS 零售系統',
                'type' => 'string',
                'group' => 'company',
                'description' => '公司名稱',
            ],
            [
                'key' => 'company_address',
                'value' => '',
                'type' => 'string',
                'group' => 'company',
                'description' => '公司地址',
            ],
            [
                'key' => 'company_phone',
                'value' => '',
                'type' => 'string',
                'group' => 'company',
                'description' => '公司電話',
            ],
            [
                'key' => 'company_email',
                'value' => '',
                'type' => 'string',
                'group' => 'company',
                'description' => '公司信箱',
            ],
            [
                'key' => 'company_tax_id',
                'value' => '',
                'type' => 'string',
                'group' => 'company',
                'description' => '統一編號',
            ],

            // 銷售設定
            [
                'key' => 'default_tax_rate',
                'value' => '5',
                'type' => 'integer',
                'group' => 'sales',
                'description' => '預設稅率 (%)',
            ],
            [
                'key' => 'allow_negative_inventory',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'sales',
                'description' => '允許負庫存銷售',
            ],
            [
                'key' => 'auto_complete_order',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'sales',
                'description' => '付款完成自動完成訂單',
            ],
            [
                'key' => 'default_payment_method',
                'value' => 'CASH',
                'type' => 'string',
                'group' => 'sales',
                'description' => '預設付款方式',
            ],

            // 會員設定
            [
                'key' => 'points_per_dollar',
                'value' => '1',
                'type' => 'integer',
                'group' => 'member',
                'description' => '每消費多少元獲得 1 點',
            ],
            [
                'key' => 'points_to_dollar',
                'value' => '100',
                'type' => 'integer',
                'group' => 'member',
                'description' => '多少點可折抵 1 元',
            ],
            [
                'key' => 'points_expiry_months',
                'value' => '12',
                'type' => 'integer',
                'group' => 'member',
                'description' => '點數有效期（月）',
            ],
            [
                'key' => 'auto_upgrade_level',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'member',
                'description' => '自動升級會員等級',
            ],

            // 庫存設定
            [
                'key' => 'low_stock_alert',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'inventory',
                'description' => '低庫存預警',
            ],
            [
                'key' => 'default_warehouse_id',
                'value' => '1',
                'type' => 'integer',
                'group' => 'inventory',
                'description' => '預設倉庫 ID',
            ],
            [
                'key' => 'fifo_costing',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'inventory',
                'description' => '使用先進先出成本計算',
            ],

            // 發票設定
            [
                'key' => 'invoice_prefix',
                'value' => 'INV',
                'type' => 'string',
                'group' => 'invoice',
                'description' => '發票編號前綴',
            ],
            [
                'key' => 'auto_print_invoice',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'invoice',
                'description' => '自動列印發票',
            ],

            // 系統設定
            [
                'key' => 'session_timeout',
                'value' => '60',
                'type' => 'integer',
                'group' => 'system',
                'description' => '登入逾時時間（分鐘）',
            ],
            [
                'key' => 'max_login_attempts',
                'value' => '5',
                'type' => 'integer',
                'group' => 'system',
                'description' => '最大登入嘗試次數',
            ],
            [
                'key' => 'audit_log_retention_days',
                'value' => '90',
                'type' => 'integer',
                'group' => 'system',
                'description' => '操作日誌保留天數',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
