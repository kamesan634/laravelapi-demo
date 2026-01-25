<?php

/**
 * 門市資料表 Migration
 *
 * 儲存門市基本資訊
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 stores 資料表
     */
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 門市基本資訊
            $table->string('code', 20)->unique()->comment('門市代碼');
            $table->string('name', 100)->comment('門市名稱');
            $table->string('short_name', 50)->nullable()->comment('門市簡稱');

            // 聯絡資訊
            $table->string('phone', 20)->nullable()->comment('電話');
            $table->string('fax', 20)->nullable()->comment('傳真');
            $table->string('email', 100)->nullable()->comment('Email');
            $table->string('address', 200)->nullable()->comment('地址');

            // 營業資訊
            $table->string('business_hours', 100)->nullable()->comment('營業時間');
            $table->string('manager', 50)->nullable()->comment('店長');

            // 狀態
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->comment('狀態');

            // 時間戳記
            $table->timestamps();

            // 索引
            $table->index('code');
            $table->index('status');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
