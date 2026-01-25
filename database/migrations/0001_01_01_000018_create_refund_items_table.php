<?php

/**
 * 退貨明細資料表 Migration
 *
 * 儲存退貨單中的商品明細
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 refund_items 資料表
     */
    public function up(): void
    {
        Schema::create('refund_items', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 關聯
            $table->foreignId('refund_id')->constrained('refunds')->onDelete('cascade')->comment('退貨單ID');
            $table->foreignId('order_item_id')->constrained('order_items')->comment('原訂單明細ID');
            $table->foreignId('product_id')->constrained('products')->comment('商品ID');

            // 數量與金額
            $table->integer('quantity')->comment('退貨數量');
            $table->decimal('unit_price', 12, 2)->comment('單價');
            $table->decimal('refund_amount', 12, 2)->comment('退款金額');

            // 庫存處理
            $table->boolean('return_to_stock')->default(true)->comment('是否退回庫存');

            // 時間戳記
            $table->timestamps();

            // 索引
            $table->index('refund_id');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_items');
    }
};
