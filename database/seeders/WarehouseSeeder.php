<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * 倉庫資料 Seeder
 */
class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        // 總倉庫（獨立倉庫）
        Warehouse::create([
            'code' => 'WH-MAIN',
            'name' => '總公司物流中心',
            'type' => 'WAREHOUSE',
            'store_id' => null,
            'address' => '新北市三重區重新路五段609號',
            'contact_person' => '黃建國',
            'phone' => '02-2999-8888',
            'is_default' => true,
            'status' => 'ACTIVE',
        ]);

        Warehouse::create([
            'code' => 'WH-NORTH',
            'name' => '北區配送中心',
            'type' => 'WAREHOUSE',
            'store_id' => null,
            'address' => '桃園市蘆竹區南崁路一段112號',
            'contact_person' => '周志偉',
            'phone' => '03-352-6666',
            'is_default' => false,
            'status' => 'ACTIVE',
        ]);

        Warehouse::create([
            'code' => 'WH-SOUTH',
            'name' => '南區配送中心',
            'type' => 'WAREHOUSE',
            'store_id' => null,
            'address' => '台南市永康區中華路456號',
            'contact_person' => '蔡明宏',
            'phone' => '06-231-5555',
            'is_default' => false,
            'status' => 'ACTIVE',
        ]);

        // 門市倉庫（與門市關聯）
        $stores = Store::all();
        foreach ($stores as $store) {
            Warehouse::create([
                'code' => "WH-{$store->code}",
                'name' => "{$store->short_name}倉庫",
                'type' => 'STORE',
                'store_id' => $store->id,
                'address' => $store->address,
                'contact_person' => $store->manager,
                'phone' => $store->phone,
                'is_default' => false,
                'status' => $store->status,
            ]);
        }
    }
}
