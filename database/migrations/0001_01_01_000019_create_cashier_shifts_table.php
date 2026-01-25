<?php

/**
 * 收銀班別資料表 Migration
 *
 * 儲存收銀員的交班與日結資訊
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 cashier_shifts 資料表
     */
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 基本資訊
            $table->foreignId('store_id')->constrained('stores')->comment('門市');
            $table->string('pos_id', 20)->comment('收銀機台');
            $table->foreignId('cashier_id')->constrained('users')->comment('收銀員');
            $table->date('shift_date')->comment('班別日期');

            // 時間
            $table->dateTime('start_time')->comment('開班時間');
            $table->dateTime('end_time')->nullable()->comment('結班時間');

            // 現金
            $table->decimal('opening_cash', 12, 2)->comment('開班現金');
            $table->decimal('expected_cash', 12, 2)->nullable()->comment('應有現金');
            $table->decimal('actual_cash', 12, 2)->nullable()->comment('實際現金');
            $table->decimal('cash_difference', 12, 2)->nullable()->comment('現金差額');
            $table->text('difference_note')->nullable()->comment('差額說明');

            // 統計
            $table->decimal('total_sales', 12, 2)->default(0)->comment('銷售總額');
            $table->decimal('total_refunds', 12, 2)->default(0)->comment('退款總額');
            $table->integer('total_transactions')->default(0)->comment('交易筆數');

            // 狀態
            $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN')->comment('狀態');

            // 核准
            $table->foreignId('approved_by')->nullable()->constrained('users')->comment('核准人');

            // 時間戳記
            $table->timestamp('created_at')->useCurrent();

            // 索引
            $table->index(['store_id', 'shift_date']);
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_shifts');
    }
};
