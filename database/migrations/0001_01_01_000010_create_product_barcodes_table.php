<?php

/**
 * 商品條碼資料表 Migration
 *
 * 支援一品多碼，儲存商品的多個條碼
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 product_barcodes 資料表
     */
    public function up(): void
    {
        Schema::create('product_barcodes', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 關聯商品
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade')->comment('商品ID');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('cascade')->comment('規格ID');

            // 條碼資訊
            $table->string('barcode', 50)->unique()->comment('條碼');
            $table->enum('barcode_type', ['EAN13', 'EAN8', 'UPCA', 'CODE39', 'CODE128', 'QRCODE'])->comment('條碼類型');
            $table->boolean('is_primary')->default(false)->comment('是否為主條碼');

            // 備註
            $table->string('notes', 100)->nullable()->comment('備註，如：舊包裝條碼');

            // 時間戳記
            $table->timestamps();

            // 索引
            $table->index('product_id');
            $table->index('barcode');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('product_barcodes');
    }
};
