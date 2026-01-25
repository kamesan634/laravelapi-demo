<?php

/**
 * 採購退貨明細資料表 Migration
 *
 * 記錄採購退貨單中的商品明細
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 purchase_return_items 資料表
     */
    public function up(): void
    {
        Schema::create('purchase_return_items', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 關聯
            $table->foreignId('return_id')->constrained('purchase_returns')->onDelete('cascade')->comment('退貨單ID');
            $table->foreignId('product_id')->constrained('products')->comment('商品ID');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->comment('規格ID');

            // 關聯原驗收明細（可選）
            $table->foreignId('receipt_item_id')->nullable()->constrained('purchase_receipt_items')->comment('驗收明細ID');

            // 數量
            $table->integer('quantity')->comment('退貨數量');

            // 價格
            $table->decimal('unit_price', 12, 2)->comment('單價');
            $table->decimal('line_total', 12, 2)->comment('行金額');

            // 批號
            $table->string('batch_no', 30)->nullable()->comment('批號');

            // 退貨原因
            $table->string('reason', 200)->nullable()->comment('退貨原因說明');

            // 時間戳記
            $table->timestamps();

            // 索引
            $table->index('return_id');
            $table->index('product_id');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
    }
};
