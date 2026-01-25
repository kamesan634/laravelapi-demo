<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 權限資料表遷移
 *
 * 儲存系統權限資料
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('權限代碼');
            $table->string('display_name', 100)->comment('顯示名稱');
            $table->string('module', 50)->comment('所屬模組');
            $table->text('description')->nullable()->comment('描述');
            $table->timestamps();

            $table->index('module');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
