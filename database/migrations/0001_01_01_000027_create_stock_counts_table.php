<?php

/**
 * 盤點單資料表 Migration
 *
 * 記錄庫存盤點作業的主檔資訊
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 stock_counts 資料表
     */
    public function up(): void
    {
        Schema::create('stock_counts', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 單據資訊
            $table->string('count_no', 20)->unique()->comment('盤點單號');
            $table->foreignId('warehouse_id')->constrained('warehouses')->comment('盤點倉庫');
            $table->date('count_date')->comment('盤點日期');

            // 盤點範圍
            $table->enum('count_type', [
                'FULL',          // 全盤
                'PARTIAL',       // 抽盤
                'CYCLE',          // 循環盤點
            ])->comment('盤點類型');

            // 盤點分類（可選，只盤特定分類）
            $table->foreignId('category_id')->nullable()->constrained('categories')->comment('盤點分類');

            // 狀態
            $table->enum('status', [
                'DRAFT',         // 草稿
                'COUNTING',      // 盤點中
                'PENDING',       // 待審核
                'APPROVED',      // 已審核
                'COMPLETED',     // 已完成
                'CANCELLED',      // 已取消
            ])->default('DRAFT')->comment('狀態');

            // 統計資訊
            $table->integer('total_items')->default(0)->comment('盤點品項數');
            $table->integer('variance_items')->default(0)->comment('差異品項數');
            $table->decimal('variance_amount', 12, 2)->default(0)->comment('差異金額');

            // 備註
            $table->text('notes')->nullable()->comment('備註');

            // 審核資訊
            $table->foreignId('approved_by')->nullable()->constrained('users')->comment('審核人');
            $table->dateTime('approved_at')->nullable()->comment('審核時間');

            // 時間戳記
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('建立者');

            // 索引
            $table->index('count_no');
            $table->index('warehouse_id');
            $table->index('count_date');
            $table->index('status');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_counts');
    }
};
