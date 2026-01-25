<?php

/**
 * 入庫單明細資料表 Migration
 *
 * 記錄入庫單中的商品明細
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 goods_receipt_items 資料表
     */
    public function up(): void
    {
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 關聯
            $table->foreignId('receipt_id')->constrained('goods_receipts')->onDelete('cascade')->comment('入庫單ID');
            $table->foreignId('product_id')->constrained('products')->comment('商品ID');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->comment('規格ID');

            // 數量
            $table->integer('expected_quantity')->comment('預期數量');
            $table->integer('received_quantity')->default(0)->comment('實收數量');

            // 成本
            $table->decimal('unit_cost', 12, 2)->nullable()->comment('單位成本');
            $table->decimal('total_cost', 12, 2)->nullable()->comment('總成本');

            // 批號與效期
            $table->string('batch_no', 30)->nullable()->comment('批號');
            $table->date('expiry_date')->nullable()->comment('效期');

            // 備註
            $table->string('notes', 200)->nullable()->comment('備註');

            // 時間戳記
            $table->timestamps();

            // 索引
            $table->index('receipt_id');
            $table->index('product_id');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};
