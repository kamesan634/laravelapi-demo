<?php

/**
 * 採購退貨單資料表 Migration
 *
 * 記錄向供應商退貨的主檔資訊
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 purchase_returns 資料表
     */
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 單據資訊
            $table->string('return_no', 20)->unique()->comment('退貨單號');
            $table->foreignId('supplier_id')->constrained('suppliers')->comment('供應商');
            $table->date('return_date')->comment('退貨日期');

            // 關聯（可選，可能來自驗收單）
            $table->foreignId('receipt_id')->nullable()->constrained('purchase_receipts')->comment('驗收單');

            // 倉庫
            $table->foreignId('warehouse_id')->constrained('warehouses')->comment('出庫倉庫');

            // 退貨原因
            $table->enum('return_reason', [
                'QUALITY',       // 品質問題
                'DAMAGED',       // 損壞
                'WRONG_ITEM',    // 品項錯誤
                'EXPIRED',       // 過期
                'OVER_DELIVERY', // 多送
                'OTHER',          // 其他
            ])->comment('退貨原因');

            // 狀態
            $table->enum('status', [
                'DRAFT',         // 草稿
                'PENDING',       // 待審核
                'APPROVED',      // 已審核
                'SHIPPED',       // 已出貨
                'COMPLETED',     // 已完成
                'CANCELLED',      // 已取消
            ])->default('DRAFT')->comment('狀態');

            // 金額
            $table->decimal('total_amount', 12, 2)->default(0)->comment('退貨總金額');

            // 備註
            $table->text('notes')->nullable()->comment('備註');

            // 審核資訊
            $table->foreignId('approved_by')->nullable()->constrained('users')->comment('審核人');
            $table->dateTime('approved_at')->nullable()->comment('審核時間');

            // 時間戳記
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('建立者');

            // 索引
            $table->index('return_no');
            $table->index('supplier_id');
            $table->index('return_date');
            $table->index('status');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
