<?php

/**
 * 盤點明細資料表 Migration
 *
 * 記錄盤點單中各商品的盤點結果
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 stock_count_items 資料表
     */
    public function up(): void
    {
        Schema::create('stock_count_items', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 關聯
            $table->foreignId('count_id')->constrained('stock_counts')->onDelete('cascade')->comment('盤點單ID');
            $table->foreignId('product_id')->constrained('products')->comment('商品ID');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->comment('規格ID');

            // 數量資訊
            $table->integer('system_quantity')->comment('系統庫存');
            $table->integer('counted_quantity')->nullable()->comment('實盤數量');
            $table->integer('variance_quantity')->nullable()->comment('差異數量');

            // 成本計算
            $table->decimal('unit_cost', 12, 2)->nullable()->comment('單位成本');
            $table->decimal('variance_amount', 12, 2)->nullable()->comment('差異金額');

            // 盤點狀態
            $table->enum('item_status', [
                'PENDING',       // 待盤點
                'COUNTED',       // 已盤點
                'RECOUNTED',      // 已複盤
            ])->default('PENDING')->comment('盤點狀態');

            // 備註
            $table->string('notes', 200)->nullable()->comment('備註');

            // 時間戳記
            $table->timestamps();
            $table->foreignId('counted_by')->nullable()->constrained('users')->comment('盤點人');

            // 索引
            $table->index('count_id');
            $table->index('product_id');
            $table->index('item_status');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_count_items');
    }
};
