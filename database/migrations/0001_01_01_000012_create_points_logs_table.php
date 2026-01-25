<?php

/**
 * 點數紀錄資料表 Migration
 *
 * 記錄會員點數的累積與兌換紀錄
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 points_logs 資料表
     */
    public function up(): void
    {
        Schema::create('points_logs', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 關聯客戶
            $table->foreignId('customer_id')->constrained('customers')->comment('客戶ID');

            // 異動資訊
            $table->enum('type', ['EARN', 'REDEEM', 'BONUS', 'ADJUST', 'EXPIRE', 'REFUND'])->comment('異動類型');
            $table->integer('points')->comment('異動點數');
            $table->integer('balance')->comment('異動後餘額');

            // 關聯單據
            $table->string('reference_type', 20)->nullable()->comment('來源類型');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('來源ID');

            // 說明
            $table->string('description', 200)->nullable()->comment('說明');
            $table->date('expire_date')->nullable()->comment('點數到期日');

            // 時間戳記
            $table->timestamp('created_at')->useCurrent();

            // 建立者
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('建立者');

            // 索引
            $table->index('customer_id');
            $table->index('created_at');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('points_logs');
    }
};
