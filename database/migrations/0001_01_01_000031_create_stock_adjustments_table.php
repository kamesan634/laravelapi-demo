<?php

/**
 * 庫存調整單資料表 Migration
 *
 * 記錄庫存數量的手動調整作業
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 stock_adjustments 資料表
     */
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 單據資訊
            $table->string('adjustment_no', 20)->unique()->comment('調整單號');
            $table->foreignId('warehouse_id')->constrained('warehouses')->comment('倉庫');
            $table->date('adjustment_date')->comment('調整日期');

            // 調整類型
            $table->enum('adjustment_type', [
                'COUNT',         // 盤點調整
                'DAMAGE',        // 損壞調整
                'EXPIRED',       // 過期調整
                'CORRECTION',    // 錯誤更正
                'OTHER',          // 其他調整
            ])->comment('調整類型');

            // 關聯（如盤點單）
            $table->string('source_type', 20)->nullable()->comment('來源類型');
            $table->unsignedBigInteger('source_id')->nullable()->comment('來源ID');

            // 商品資訊
            $table->foreignId('product_id')->constrained('products')->comment('商品ID');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->comment('規格ID');

            // 數量
            $table->integer('before_quantity')->comment('調整前數量');
            $table->integer('adjust_quantity')->comment('調整數量（正負）');
            $table->integer('after_quantity')->comment('調整後數量');

            // 成本
            $table->decimal('unit_cost', 12, 2)->nullable()->comment('單位成本');
            $table->decimal('adjustment_value', 12, 2)->nullable()->comment('調整金額');

            // 狀態
            $table->enum('status', [
                'DRAFT',         // 草稿
                'PENDING',       // 待審核
                'APPROVED',      // 已審核
                'COMPLETED',     // 已完成
                'CANCELLED',      // 已取消
            ])->default('DRAFT')->comment('狀態');

            // 原因說明
            $table->text('reason')->comment('調整原因');

            // 審核資訊
            $table->foreignId('approved_by')->nullable()->constrained('users')->comment('審核人');
            $table->dateTime('approved_at')->nullable()->comment('審核時間');

            // 時間戳記
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('建立者');

            // 索引
            $table->index('adjustment_no');
            $table->index('warehouse_id');
            $table->index('adjustment_date');
            $table->index('product_id');
            $table->index('status');
            $table->index(['source_type', 'source_id']);
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
