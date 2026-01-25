<?php

/**
 * 倉庫資料表 Migration
 *
 * 儲存倉庫與門市庫存位置資訊
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 warehouses 資料表
     */
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 倉庫基本資訊
            $table->string('code', 20)->unique()->comment('倉庫代碼');
            $table->string('name', 100)->comment('倉庫名稱');
            $table->enum('type', ['STORE', 'WAREHOUSE'])->comment('倉庫類型：門市/獨立倉庫');

            // 關聯門市（門市倉必填）
            $table->foreignId('store_id')->nullable()->constrained('stores')->comment('所屬門市');

            // 聯絡資訊
            $table->string('address', 200)->nullable()->comment('地址');
            $table->string('contact_person', 50)->nullable()->comment('聯絡人');
            $table->string('phone', 20)->nullable()->comment('電話');

            // 設定
            $table->boolean('is_default')->default(false)->comment('是否為預設倉庫');

            // 狀態
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->comment('狀態');

            // 時間戳記
            $table->timestamps();

            // 索引
            $table->index('code');
            $table->index('type');
            $table->index('store_id');
            $table->index('status');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
