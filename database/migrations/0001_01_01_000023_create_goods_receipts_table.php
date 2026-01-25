<?php

/**
 * 入庫單資料表 Migration
 *
 * 記錄各種入庫作業的主檔資訊
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 goods_receipts 資料表
     */
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 單據資訊
            $table->string('receipt_no', 20)->unique()->comment('入庫單號');
            $table->foreignId('warehouse_id')->constrained('warehouses')->comment('入庫倉庫');
            $table->date('receipt_date')->comment('入庫日期');

            // 入庫類型
            $table->enum('receipt_type', [
                'PURCHASE',      // 採購入庫
                'RETURN',        // 退貨入庫
                'TRANSFER',      // 調撥入庫
                'ADJUST',        // 調整入庫
                'OTHER',          // 其他入庫
            ])->comment('入庫類型');

            // 來源單據
            $table->string('source_type', 20)->nullable()->comment('來源類型');
            $table->unsignedBigInteger('source_id')->nullable()->comment('來源ID');
            $table->string('source_no', 30)->nullable()->comment('來源單號');

            // 狀態
            $table->enum('status', [
                'DRAFT',         // 草稿
                'PENDING',       // 待審核
                'APPROVED',      // 已審核
                'COMPLETED',     // 已完成
                'CANCELLED',      // 已取消
            ])->default('DRAFT')->comment('狀態');

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
            $table->index('warehouse_id');
            $table->index('receipt_date');
            $table->index('status');
            $table->index(['source_type', 'source_id']);
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
