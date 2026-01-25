<?php

/**
 * 付款紀錄資料表 Migration
 *
 * 儲存訂單的付款資訊，支援複合付款
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 payments 資料表
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 關聯訂單
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade')->comment('訂單ID');

            // 付款資訊
            $table->string('payment_method', 20)->comment('付款方式');
            $table->decimal('amount', 12, 2)->comment('付款金額');
            $table->decimal('received_amount', 12, 2)->nullable()->comment('收款金額');
            $table->decimal('change_amount', 12, 2)->nullable()->comment('找零金額');

            // 信用卡資訊
            $table->string('card_last_four', 4)->nullable()->comment('卡號末四碼');
            $table->string('auth_code', 20)->nullable()->comment('授權碼');
            $table->string('reference_no', 50)->nullable()->comment('參考編號');

            // 狀態
            $table->enum('status', ['SUCCESS', 'FAILED', 'REFUNDED'])->default('SUCCESS')->comment('狀態');

            // 時間戳記
            $table->timestamp('created_at')->useCurrent();

            // 索引
            $table->index('order_id');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
