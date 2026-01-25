<?php

namespace Database\Seeders;

use App\Models\CustomerLevel;
use Illuminate\Database\Seeder;

/**
 * 會員等級 Seeder
 */
class CustomerLevelSeeder extends Seeder
{
    public function run(): void
    {
        CustomerLevel::create([
            'level_code' => 1,
            'name' => '一般會員',
            'spending_threshold' => 0,
            'maintain_threshold' => 0,
            'discount_rate' => 0,
            'points_multiplier' => 1.0,
            'benefits' => json_encode([
                '生日禮金 100 元',
                '會員專屬優惠通知',
            ]),
            'status' => 'ACTIVE',
        ]);

        CustomerLevel::create([
            'level_code' => 2,
            'name' => '銀卡會員',
            'spending_threshold' => 10000,
            'maintain_threshold' => 5000,
            'discount_rate' => 5,
            'points_multiplier' => 1.2,
            'benefits' => json_encode([
                '全館 95 折',
                '生日禮金 200 元',
                '點數 1.2 倍',
                '免費包裝服務',
            ]),
            'status' => 'ACTIVE',
        ]);

        CustomerLevel::create([
            'level_code' => 3,
            'name' => '金卡會員',
            'spending_threshold' => 30000,
            'maintain_threshold' => 15000,
            'discount_rate' => 10,
            'points_multiplier' => 1.5,
            'benefits' => json_encode([
                '全館 9 折',
                '生日禮金 500 元',
                '點數 1.5 倍',
                '免費包裝服務',
                '優先客服通道',
                '新品優先購買權',
            ]),
            'status' => 'ACTIVE',
        ]);

        CustomerLevel::create([
            'level_code' => 4,
            'name' => '白金會員',
            'spending_threshold' => 100000,
            'maintain_threshold' => 50000,
            'discount_rate' => 15,
            'points_multiplier' => 2.0,
            'benefits' => json_encode([
                '全館 85 折',
                '生日禮金 1000 元',
                '點數 2 倍',
                '免費包裝服務',
                '專屬客服經理',
                '新品優先購買權',
                'VIP 專屬活動邀請',
                '免費到府服務',
            ]),
            'status' => 'ACTIVE',
        ]);

        CustomerLevel::create([
            'level_code' => 5,
            'name' => '鑽石會員',
            'spending_threshold' => 300000,
            'maintain_threshold' => 150000,
            'discount_rate' => 20,
            'points_multiplier' => 3.0,
            'benefits' => json_encode([
                '全館 8 折',
                '生日禮金 3000 元',
                '點數 3 倍',
                '免費包裝服務',
                '專屬客服經理',
                '新品優先購買權',
                'VIP 專屬活動邀請',
                '免費到府服務',
                '年度禮物',
                '專屬停車位',
            ]),
            'status' => 'ACTIVE',
        ]);
    }
}
