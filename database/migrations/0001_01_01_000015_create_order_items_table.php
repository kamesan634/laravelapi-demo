<?php

/**
 * 訂單明細資料表 Migration
 *
 * 儲存訂單中的商品明細
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 order_items 資料表
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 關聯訂單
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade')->comment('訂單ID');

            // 商品資訊
            $table->foreignId('product_id')->constrained('products')->comment('商品ID');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->comment('規格ID');
            $table->string('product_name', 200)->comment('商品名稱（當時）');
            $table->string('sku', 50)->nullable()->comment('SKU');

            // 數量與價格
            $table->integer('quantity')->comment('數量');
            $table->decimal('unit_price', 12, 2)->comment('單價');
            $table->decimal('original_price', 12, 2)->comment('原價');
            $table->decimal('discount_amount', 12, 2)->default(0)->comment('折扣金額');
            $table->decimal('tax_amount', 12, 2)->default(0)->comment('稅額');
            $table->decimal('subtotal', 12, 2)->comment('小計');

            // 成本（用於毛利計算）
            $table->decimal('cost_price', 12, 2)->nullable()->comment('成本價');

            // 促銷
            $table->unsignedBigInteger('promotion_id')->nullable()->comment('套用促銷');

            // 備註
            $table->string('notes', 200)->nullable()->comment('備註');

            // 時間戳記
            $table->timestamps();

            // 索引
            $table->index('order_id');
            $table->index('product_id');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
