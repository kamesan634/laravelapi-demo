<?php

/**
 * 商品規格資料表 Migration
 *
 * 儲存商品的多規格變體（如尺寸、顏色）
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 product_variants 資料表
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 關聯主商品
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade')->comment('父商品ID');

            // 規格基本資訊
            $table->string('sku', 50)->unique()->comment('SKU編號');
            $table->string('barcode', 30)->nullable()->unique()->comment('條碼');
            $table->json('variant_options')->comment('規格組合，如 {"顏色":"白色","尺寸":"M"}');

            // 價格（可覆寫主商品價格）
            $table->decimal('cost_price', 12, 2)->nullable()->comment('成本價');
            $table->decimal('selling_price', 12, 2)->nullable()->comment('售價');

            // 圖片
            $table->string('image_url', 500)->nullable()->comment('規格圖片');

            // 狀態
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->comment('狀態');

            // 時間戳記
            $table->timestamps();

            // 索引
            $table->index('product_id');
            $table->index('status');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
