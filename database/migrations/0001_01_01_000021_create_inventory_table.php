<?php

/**
 * 庫存資料表 Migration
 *
 * 儲存各倉庫的商品庫存數量
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 inventory 資料表
     */
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 關聯
            $table->foreignId('product_id')->constrained('products')->comment('商品ID');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->comment('規格ID');
            $table->foreignId('warehouse_id')->constrained('warehouses')->comment('倉庫ID');

            // 庫存數量
            $table->integer('quantity')->default(0)->comment('現有庫存');
            $table->integer('reserved_quantity')->default(0)->comment('預留數量');
            // 可用庫存 = 現有庫存 - 預留數量，使用虛擬欄位計算

            // 盤點資訊
            $table->date('last_count_date')->nullable()->comment('最後盤點日期');
            $table->dateTime('last_movement_date')->nullable()->comment('最後異動日期');

            // 時間戳記
            $table->timestamps();

            // 唯一約束
            $table->unique(['product_id', 'variant_id', 'warehouse_id'], 'uk_product_warehouse');

            // 索引
            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index('quantity');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
