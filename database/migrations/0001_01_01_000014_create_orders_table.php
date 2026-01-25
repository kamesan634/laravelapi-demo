<?php

/**
 * 銷售訂單資料表 Migration
 *
 * 儲存 POS 銷售訂單表頭資訊
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 orders 資料表
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 訂單基本資訊
            $table->string('order_no', 30)->unique()->comment('訂單編號');
            $table->foreignId('store_id')->constrained('stores')->comment('門市');
            $table->string('pos_id', 20)->nullable()->comment('收銀機台');
            $table->foreignId('cashier_id')->constrained('users')->comment('收銀員');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->comment('會員');
            $table->dateTime('order_date')->comment('訂單日期');

            // 金額
            $table->decimal('subtotal', 12, 2)->comment('商品小計');
            $table->decimal('discount_amount', 12, 2)->default(0)->comment('折扣金額');
            $table->decimal('tax_amount', 12, 2)->default(0)->comment('稅額');
            $table->decimal('total_amount', 12, 2)->comment('訂單總額');

            // 點數
            $table->integer('points_earned')->default(0)->comment('獲得點數');
            $table->integer('points_used')->default(0)->comment('使用點數');
            $table->decimal('points_amount', 12, 2)->default(0)->comment('點數折抵金額');

            // 狀態
            $table->enum('status', ['COMPLETED', 'VOIDED', 'REFUNDED', 'PARTIAL_REFUND'])->default('COMPLETED')->comment('訂單狀態');

            // 促銷
            $table->json('promotion_ids')->nullable()->comment('套用的促銷活動');
            $table->string('coupon_code', 30)->nullable()->comment('折扣碼');

            // 其他
            $table->text('notes')->nullable()->comment('備註');

            // 時間戳記
            $table->timestamps();

            // 索引
            $table->index('order_no');
            $table->index(['store_id', 'order_date']);
            $table->index('customer_id');
            $table->index('status');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
