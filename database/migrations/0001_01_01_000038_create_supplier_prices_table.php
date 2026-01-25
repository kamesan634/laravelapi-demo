<?php

/**
 * 供應商報價資料表 Migration
 *
 * 記錄各供應商對商品的報價資訊
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 supplier_prices 資料表
     */
    public function up(): void
    {
        Schema::create('supplier_prices', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 關聯
            $table->foreignId('supplier_id')->constrained('suppliers')->comment('供應商');
            $table->foreignId('product_id')->constrained('products')->comment('商品ID');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->comment('規格ID');

            // 價格
            $table->decimal('unit_price', 12, 2)->comment('單價');
            $table->decimal('min_quantity', 10, 2)->default(1)->comment('最小訂購量');

            // 供應商商品編號
            $table->string('supplier_sku', 30)->nullable()->comment('供應商料號');

            // 前置時間
            $table->integer('lead_days')->default(0)->comment('前置天數');

            // 有效期間
            $table->date('effective_from')->comment('生效日期');
            $table->date('effective_to')->nullable()->comment('失效日期');

            // 是否為主要供應商
            $table->boolean('is_primary')->default(false)->comment('是否主要供應商');

            // 狀態
            $table->boolean('is_active')->default(true)->comment('是否啟用');

            // 備註
            $table->string('notes', 200)->nullable()->comment('備註');

            // 時間戳記
            $table->timestamps();

            // 唯一約束
            $table->unique(['supplier_id', 'product_id', 'variant_id', 'effective_from'], 'uk_supplier_product_date');

            // 索引
            $table->index('supplier_id');
            $table->index('product_id');
            $table->index(['effective_from', 'effective_to']);
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_prices');
    }
};
