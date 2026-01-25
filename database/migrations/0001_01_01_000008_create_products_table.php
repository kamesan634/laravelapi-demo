<?php

/**
 * 商品主檔資料表 Migration
 *
 * 儲存商品基本資料、價格、庫存設定等
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 products 資料表
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 基本資訊
            $table->string('sku', 30)->unique()->comment('商品編號');
            $table->string('barcode', 30)->nullable()->unique()->comment('商品條碼');
            $table->string('name', 200)->comment('商品名稱');
            $table->string('short_name', 50)->nullable()->comment('商品簡稱，POS顯示用');

            // 分類與品牌
            $table->foreignId('category_id')->constrained('categories')->comment('商品分類');
            $table->string('brand', 50)->nullable()->comment('品牌');
            $table->string('description', 500)->nullable()->comment('規格說明');

            // 計量
            $table->string('unit', 10)->comment('計量單位');

            // 價格資訊
            $table->decimal('cost_price', 12, 2)->default(0)->comment('成本價');
            $table->decimal('selling_price', 12, 2)->comment('標準售價');
            $table->decimal('member_price', 12, 2)->nullable()->comment('會員價');
            $table->decimal('min_price', 12, 2)->nullable()->comment('最低售價');
            $table->string('tax_type', 10)->default('TAX')->comment('稅別');

            // 庫存設定
            $table->integer('safety_stock')->default(0)->comment('安全庫存量');
            $table->integer('max_stock')->nullable()->comment('最大庫存量');
            $table->boolean('track_inventory')->default(true)->comment('是否啟用庫存管理');

            // 供應商
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->comment('主要供應商');

            // 圖片與備註
            $table->string('image_url', 500)->nullable()->comment('商品圖片URL');
            $table->text('notes')->nullable()->comment('備註');

            // 狀態
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'DISCONTINUED'])->default('ACTIVE')->comment('狀態');

            // 時間戳記
            $table->timestamps();

            // 建立者
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('建立者');

            // 索引
            $table->index('sku');
            $table->index('barcode');
            $table->index('category_id');
            $table->index('status');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
