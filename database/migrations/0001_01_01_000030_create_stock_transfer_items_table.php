<?php

/**
 * 調撥明細資料表 Migration
 *
 * 記錄調撥單中的商品明細
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 stock_transfer_items 資料表
     */
    public function up(): void
    {
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 關聯
            $table->foreignId('transfer_id')->constrained('stock_transfers')->onDelete('cascade')->comment('調撥單ID');
            $table->foreignId('product_id')->constrained('products')->comment('商品ID');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->comment('規格ID');

            // 數量
            $table->integer('quantity')->comment('調撥數量');
            $table->integer('shipped_quantity')->default(0)->comment('已出庫數量');
            $table->integer('received_quantity')->default(0)->comment('已收貨數量');

            // 成本
            $table->decimal('unit_cost', 12, 2)->nullable()->comment('單位成本');

            // 批號
            $table->string('batch_no', 30)->nullable()->comment('批號');

            // 備註
            $table->string('notes', 200)->nullable()->comment('備註');

            // 時間戳記
            $table->timestamps();

            // 索引
            $table->index('transfer_id');
            $table->index('product_id');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
    }
};
