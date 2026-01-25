<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * 角色資料填充
 */
class RoleSeeder extends Seeder
{
    /**
     * 執行資料填充
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => '超級管理員',
                'description' => '擁有系統所有權限',
                'is_active' => true,
            ],
            [
                'name' => 'admin',
                'display_name' => '管理員',
                'description' => '擁有大部分管理權限',
                'is_active' => true,
            ],
            [
                'name' => 'manager',
                'display_name' => '店長',
                'description' => '門市管理權限',
                'is_active' => true,
            ],
            [
                'name' => 'cashier',
                'display_name' => '收銀員',
                'description' => '收銀相關權限',
                'is_active' => true,
            ],
            [
                'name' => 'warehouse',
                'display_name' => '倉管人員',
                'description' => '庫存管理權限',
                'is_active' => true,
            ],
            [
                'name' => 'purchaser',
                'display_name' => '採購人員',
                'description' => '採購相關權限',
                'is_active' => true,
            ],
            [
                'name' => 'viewer',
                'display_name' => '檢視者',
                'description' => '只能檢視資料',
                'is_active' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
