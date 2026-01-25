<?php

/**
 * 促銷活動資料表 Migration
 *
 * 儲存各種促銷折扣活動設定
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 promotions 資料表
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 基本資訊
            $table->string('code', 30)->unique()->comment('活動代碼');
            $table->string('name', 100)->comment('活動名稱');
            $table->string('description', 500)->nullable()->comment('活動描述');

            // 促銷類型
            $table->string('promotion_type', 30)->comment('促銷類型');

            // 時間設定
            $table->dateTime('start_time')->comment('開始時間');
            $table->dateTime('end_time')->comment('結束時間');

            // 適用範圍
            $table->json('applicable_products')->nullable()->comment('適用商品');
            $table->json('applicable_categories')->nullable()->comment('適用分類');
            $table->json('excluded_products')->nullable()->comment('排除商品');
            $table->json('applicable_stores')->nullable()->comment('適用門市');
            $table->json('applicable_member_levels')->nullable()->comment('適用會員等級');

            // 促銷規則
            $table->json('conditions')->comment('促銷條件');
            $table->json('discount_rules')->comment('折扣規則');

            // 使用限制
            $table->integer('usage_limit_per_customer')->nullable()->comment('每人使用次數上限');
            $table->integer('total_usage_limit')->nullable()->comment('活動總使用次數上限');
            $table->integer('current_usage')->default(0)->comment('目前已使用次數');

            // 優先設定
            $table->boolean('stackable')->default(false)->comment('可否與其他優惠併用');
            $table->integer('priority')->default(0)->comment('優先順序');

            // 狀態
            $table->enum('status', ['DRAFT', 'ACTIVE', 'INACTIVE', 'EXPIRED'])->default('DRAFT')->comment('狀態');

            // 時間戳記
            $table->timestamps();

            // 建立者
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('建立者');

            // 索引
            $table->index('code');
            $table->index(['status', 'start_time', 'end_time']);
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
