<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

/**
 * 門市資料 Seeder
 */
class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $stores = [
            [
                'code' => 'S001',
                'name' => '台北信義旗艦店',
                'short_name' => '信義店',
                'phone' => '02-2345-6789',
                'fax' => '02-2345-6780',
                'email' => 'xinyi@demo-pos.com',
                'address' => '台北市信義區信義路五段7號',
                'business_hours' => '10:00-22:00',
                'manager' => '王大明',
                'status' => 'ACTIVE',
            ],
            [
                'code' => 'S002',
                'name' => '台北西門店',
                'short_name' => '西門店',
                'phone' => '02-2388-1234',
                'fax' => '02-2388-1230',
                'email' => 'ximen@demo-pos.com',
                'address' => '台北市萬華區西門町18號',
                'business_hours' => '11:00-23:00',
                'manager' => '李小華',
                'status' => 'ACTIVE',
            ],
            [
                'code' => 'S003',
                'name' => '台中逢甲店',
                'short_name' => '逢甲店',
                'phone' => '04-2451-6789',
                'fax' => '04-2451-6780',
                'email' => 'fengjia@demo-pos.com',
                'address' => '台中市西屯區福星路316號',
                'business_hours' => '12:00-24:00',
                'manager' => '張美玲',
                'status' => 'ACTIVE',
            ],
            [
                'code' => 'S004',
                'name' => '高雄夢時代店',
                'short_name' => '夢時代店',
                'phone' => '07-812-3456',
                'fax' => '07-812-3450',
                'email' => 'dream@demo-pos.com',
                'address' => '高雄市前鎮區中華五路789號',
                'business_hours' => '11:00-22:00',
                'manager' => '陳志明',
                'status' => 'ACTIVE',
            ],
            [
                'code' => 'S005',
                'name' => '新竹巨城店',
                'short_name' => '巨城店',
                'phone' => '03-535-6789',
                'fax' => '03-535-6780',
                'email' => 'bigcity@demo-pos.com',
                'address' => '新竹市東區中央路229號',
                'business_hours' => '11:00-22:00',
                'manager' => '林怡君',
                'status' => 'INACTIVE',
            ],
        ];

        foreach ($stores as $store) {
            Store::create($store);
        }
    }
}
