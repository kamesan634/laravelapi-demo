<?php

/**
 * 客戶/會員資料表 Migration
 *
 * 儲存客戶與會員的基本資料
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 customers 資料表
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 基本資訊
            $table->string('member_no', 20)->unique()->comment('會員編號');
            $table->string('name', 50)->comment('姓名');
            $table->enum('gender', ['M', 'F', 'OTHER'])->nullable()->comment('性別');
            $table->date('birthday')->nullable()->comment('生日');

            // 聯絡資訊
            $table->string('phone', 20)->unique()->comment('手機，登入識別用');
            $table->string('email', 100)->nullable()->comment('Email');
            $table->string('address', 200)->nullable()->comment('地址');

            // 會員等級
            $table->foreignId('level_id')->default(1)->constrained('customer_levels')->comment('會員等級');

            // 消費統計
            $table->decimal('total_spending', 14, 2)->default(0)->comment('累積消費');
            $table->integer('total_points')->default(0)->comment('累積點數');
            $table->integer('available_points')->default(0)->comment('可用點數');

            // 來源資訊
            $table->date('join_date')->comment('加入日期');
            $table->foreignId('join_store_id')->nullable()->constrained('stores')->comment('加入門市');

            // 其他
            $table->text('notes')->nullable()->comment('備註');

            // 狀態
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->comment('狀態');

            // 時間戳記
            $table->timestamps();

            // 索引
            $table->index('phone');
            $table->index('member_no');
            $table->index('level_id');
            $table->index('status');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
