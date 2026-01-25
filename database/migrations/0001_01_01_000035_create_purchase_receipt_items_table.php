<?php

/**
 * 進貨驗收明細資料表 Migration
 *
 * 記錄驗收單中的商品明細
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 purchase_receipt_items 資料表
     */
    public function up(): void
    {
        Schema::create('purchase_receipt_items', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 關聯
            $table->foreignId('receipt_id')->constrained('purchase_receipts')->onDelete('cascade')->comment('驗收單ID');
            $table->foreignId('po_item_id')->constrained('purchase_order_items')->comment('採購明細ID');
            $table->foreignId('product_id')->constrained('products')->comment('商品ID');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->comment('規格ID');

            // 數量
            $table->integer('expected_quantity')->comment('應收數量');
            $table->integer('received_quantity')->comment('實收數量');
            $table->integer('rejected_quantity')->default(0)->comment('退回數量');

            // 價格
            $table->decimal('unit_price', 12, 2)->comment('單價');
            $table->decimal('line_total', 12, 2)->comment('行金額');

            // 批號與效期
            $table->string('batch_no', 30)->nullable()->comment('批號');
            $table->date('expiry_date')->nullable()->comment('效期');

            // 品質
            $table->enum('quality_status', [
                'PASSED',        // 合格
                'FAILED',        // 不合格
                'PARTIAL',        // 部分合格
            ])->default('PASSED')->comment('品質狀態');
            $table->string('quality_notes', 200)->nullable()->comment('品質備註');

            // 時間戳記
            $table->timestamps();

            // 索引
            $table->index('receipt_id');
            $table->index('po_item_id');
            $table->index('product_id');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_receipt_items');
    }
};
