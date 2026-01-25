<?php

/**
 * 採購單資料表 Migration
 *
 * 記錄採購訂單的主檔資訊
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 purchase_orders 資料表
     */
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 單據資訊
            $table->string('po_no', 20)->unique()->comment('採購單號');
            $table->foreignId('supplier_id')->constrained('suppliers')->comment('供應商');
            $table->date('order_date')->comment('訂購日期');
            $table->date('expected_date')->nullable()->comment('預計到貨日');

            // 目的倉庫
            $table->foreignId('warehouse_id')->constrained('warehouses')->comment('入庫倉庫');

            // 狀態
            $table->enum('status', [
                'DRAFT',         // 草稿
                'PENDING',       // 待審核
                'APPROVED',      // 已審核
                'PARTIAL',       // 部分到貨
                'COMPLETED',     // 已完成
                'CANCELLED',      // 已取消
            ])->default('DRAFT')->comment('狀態');

            // 金額
            $table->decimal('subtotal', 12, 2)->default(0)->comment('小計');
            $table->decimal('tax_amount', 12, 2)->default(0)->comment('稅額');
            $table->decimal('total_amount', 12, 2)->default(0)->comment('總金額');

            // 付款條件
            $table->string('payment_terms', 50)->nullable()->comment('付款條件');

            // 備註
            $table->text('notes')->nullable()->comment('備註');

            // 審核資訊
            $table->foreignId('approved_by')->nullable()->constrained('users')->comment('審核人');
            $table->dateTime('approved_at')->nullable()->comment('審核時間');

            // 時間戳記
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('建立者');

            // 索引
            $table->index('po_no');
            $table->index('supplier_id');
            $table->index('order_date');
            $table->index('status');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
