<?php

/**
 * 調撥單資料表 Migration
 *
 * 記錄倉庫間的庫存調撥作業
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 stock_transfers 資料表
     */
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 單據資訊
            $table->string('transfer_no', 20)->unique()->comment('調撥單號');
            $table->date('transfer_date')->comment('調撥日期');

            // 倉庫資訊
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->comment('調出倉庫');
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->comment('調入倉庫');

            // 狀態
            $table->enum('status', [
                'DRAFT',         // 草稿
                'PENDING',       // 待審核
                'APPROVED',      // 已審核（待出庫）
                'IN_TRANSIT',    // 運送中
                'RECEIVED',      // 已收貨
                'COMPLETED',     // 已完成
                'CANCELLED',      // 已取消
            ])->default('DRAFT')->comment('狀態');

            // 備註
            $table->text('notes')->nullable()->comment('備註');

            // 審核資訊
            $table->foreignId('approved_by')->nullable()->constrained('users')->comment('審核人');
            $table->dateTime('approved_at')->nullable()->comment('審核時間');

            // 出庫資訊
            $table->foreignId('shipped_by')->nullable()->constrained('users')->comment('出庫人');
            $table->dateTime('shipped_at')->nullable()->comment('出庫時間');

            // 收貨資訊
            $table->foreignId('received_by')->nullable()->constrained('users')->comment('收貨人');
            $table->dateTime('received_at')->nullable()->comment('收貨時間');

            // 時間戳記
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('建立者');

            // 索引
            $table->index('transfer_no');
            $table->index('from_warehouse_id');
            $table->index('to_warehouse_id');
            $table->index('transfer_date');
            $table->index('status');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
