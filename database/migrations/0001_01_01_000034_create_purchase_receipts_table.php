<?php

/**
 * 進貨驗收單資料表 Migration
 *
 * 記錄採購訂單的到貨驗收資訊
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 purchase_receipts 資料表
     */
    public function up(): void
    {
        Schema::create('purchase_receipts', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 單據資訊
            $table->string('receipt_no', 20)->unique()->comment('驗收單號');
            $table->foreignId('po_id')->constrained('purchase_orders')->comment('採購單');
            $table->foreignId('supplier_id')->constrained('suppliers')->comment('供應商');
            $table->date('receipt_date')->comment('驗收日期');

            // 倉庫
            $table->foreignId('warehouse_id')->constrained('warehouses')->comment('入庫倉庫');

            // 狀態
            $table->enum('status', [
                'DRAFT',         // 草稿
                'PENDING',       // 待審核
                'APPROVED',      // 已審核
                'COMPLETED',     // 已完成（已入庫）
                'CANCELLED',      // 已取消
            ])->default('DRAFT')->comment('狀態');

            // 金額
            $table->decimal('total_amount', 12, 2)->default(0)->comment('驗收總金額');

            // 供應商單據
            $table->string('supplier_invoice_no', 30)->nullable()->comment('供應商發票號');
            $table->date('supplier_invoice_date')->nullable()->comment('供應商發票日期');

            // 備註
            $table->text('notes')->nullable()->comment('備註');

            // 審核資訊
            $table->foreignId('approved_by')->nullable()->constrained('users')->comment('審核人');
            $table->dateTime('approved_at')->nullable()->comment('審核時間');

            // 時間戳記
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('建立者');

            // 索引
            $table->index('receipt_no');
            $table->index('po_id');
            $table->index('supplier_id');
            $table->index('receipt_date');
            $table->index('status');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_receipts');
    }
};
