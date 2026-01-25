<?php

namespace Database\Seeders;

use App\Services\NumberSequenceService;
use Illuminate\Database\Seeder;

/**
 * 編號規則資料填充
 */
class NumberSequenceSeeder extends Seeder
{
    /**
     * 執行資料填充
     */
    public function run(): void
    {
        $service = new NumberSequenceService;
        $service->initializeDefaults();
    }
}
