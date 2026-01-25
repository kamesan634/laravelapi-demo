<?php

/**
 * 供應商報價歷史資料表 Migration
 *
 * 記錄供應商報價的變更歷史
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 supplier_price_history 資料表
     */
    public function up(): void
    {
        Schema::create('supplier_price_history', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 關聯
            $table->foreignId('supplier_price_id')->constrained('supplier_prices')->onDelete('cascade')->comment('報價ID');
            $table->foreignId('supplier_id')->constrained('suppliers')->comment('供應商');
            $table->foreignId('product_id')->constrained('products')->comment('商品ID');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->comment('規格ID');

            // 變更前價格
            $table->decimal('old_price', 12, 2)->nullable()->comment('舊價格');

            // 變更後價格
            $table->decimal('new_price', 12, 2)->comment('新價格');

            // 變更幅度
            $table->decimal('price_change', 12, 2)->nullable()->comment('價格變化');
            $table->decimal('change_percentage', 8, 2)->nullable()->comment('變化百分比');

            // 變更原因
            $table->string('change_reason', 200)->nullable()->comment('變更原因');

            // 變更日期
            $table->date('effective_date')->comment('生效日期');

            // 時間戳記
            $table->timestamp('created_at')->useCurrent();
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('建立者');

            // 索引
            $table->index('supplier_price_id');
            $table->index('supplier_id');
            $table->index('product_id');
            $table->index('effective_date');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_price_history');
    }
};
