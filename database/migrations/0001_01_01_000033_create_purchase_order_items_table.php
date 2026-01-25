<?php

/**
 * 採購單明細資料表 Migration
 *
 * 記錄採購單中的商品明細
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 purchase_order_items 資料表
     */
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 關聯
            $table->foreignId('po_id')->constrained('purchase_orders')->onDelete('cascade')->comment('採購單ID');
            $table->foreignId('product_id')->constrained('products')->comment('商品ID');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->comment('規格ID');

            // 數量
            $table->integer('quantity')->comment('訂購數量');
            $table->integer('received_quantity')->default(0)->comment('已收貨數量');

            // 價格
            $table->decimal('unit_price', 12, 2)->comment('單價');
            $table->decimal('discount_rate', 5, 2)->default(0)->comment('折扣率');
            $table->decimal('line_total', 12, 2)->comment('行金額');

            // 備註
            $table->string('notes', 200)->nullable()->comment('備註');

            // 時間戳記
            $table->timestamps();

            // 索引
            $table->index('po_id');
            $table->index('product_id');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
