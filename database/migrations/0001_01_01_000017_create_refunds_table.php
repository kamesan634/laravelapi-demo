<?php

/**
 * 退貨單資料表 Migration
 *
 * 儲存客戶退貨的表頭資訊
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 refunds 資料表
     */
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 退貨基本資訊
            $table->string('refund_no', 30)->unique()->comment('退貨單號');
            $table->foreignId('order_id')->constrained('orders')->comment('原訂單ID');
            $table->foreignId('store_id')->constrained('stores')->comment('門市');
            $table->foreignId('cashier_id')->constrained('users')->comment('收銀員');
            $table->foreignId('approver_id')->nullable()->constrained('users')->comment('核准主管');

            // 退貨資訊
            $table->dateTime('refund_date')->comment('退貨日期');
            $table->enum('refund_type', ['REFUND', 'EXCHANGE', 'REFUND_ONLY'])->comment('退貨類型');
            $table->string('reason_code', 20)->comment('退貨原因碼');
            $table->text('reason_note')->nullable()->comment('退貨原因說明');

            // 金額
            $table->decimal('refund_amount', 12, 2)->comment('退款金額');
            $table->string('refund_method', 20)->comment('退款方式');

            // 點數
            $table->integer('points_deducted')->default(0)->comment('扣減點數');

            // 狀態
            $table->enum('status', ['COMPLETED', 'CANCELLED'])->default('COMPLETED')->comment('狀態');

            // 時間戳記
            $table->timestamp('created_at')->useCurrent();

            // 索引
            $table->index('refund_no');
            $table->index('order_id');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
