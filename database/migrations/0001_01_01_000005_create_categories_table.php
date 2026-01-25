<?php

/**
 * 商品分類資料表 Migration
 *
 * 儲存商品階層式分類結構，支援無限層級
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 categories 資料表
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 分類基本資訊
            $table->string('code', 20)->unique()->comment('分類代碼');
            $table->string('name', 50)->comment('分類名稱');

            // 階層結構
            $table->unsignedBigInteger('parent_id')->nullable()->comment('父分類ID');
            $table->integer('level')->default(1)->comment('分類層級');
            $table->string('path', 200)->nullable()->comment('分類路徑');

            // 顯示設定
            $table->integer('sort_order')->default(0)->comment('排序順序');
            $table->string('icon', 100)->nullable()->comment('圖示');

            // 狀態
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->comment('狀態');

            // 時間戳記
            $table->timestamps();

            // 外鍵約束（自我關聯）
            $table->foreign('parent_id')->references('id')->on('categories');

            // 索引
            $table->index('parent_id');
            $table->index('path');
            $table->index('status');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
