<?php

/**
 * 會員等級資料表 Migration
 *
 * 儲存會員等級與升降級規則設定
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 customer_levels 資料表
     */
    public function up(): void
    {
        Schema::create('customer_levels', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 等級基本資訊
            $table->integer('level_code')->unique()->comment('等級代碼，數字越大等級越高');
            $table->string('name', 50)->comment('等級名稱');

            // 門檻設定
            $table->decimal('spending_threshold', 12, 2)->default(0)->comment('累積消費門檻');
            $table->decimal('maintain_threshold', 12, 2)->nullable()->comment('維持消費門檻');

            // 優惠設定
            $table->decimal('discount_rate', 5, 2)->default(0)->comment('折扣比例 %');
            $table->decimal('points_multiplier', 3, 1)->default(1.0)->comment('點數倍率');
            $table->json('benefits')->nullable()->comment('專屬優惠設定');

            // 狀態
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->comment('狀態');

            // 時間戳記
            $table->timestamps();

            // 索引
            $table->index('level_code');
            $table->index('status');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_levels');
    }
};
