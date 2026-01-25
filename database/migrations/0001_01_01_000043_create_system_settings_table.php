<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 系統設定資料表遷移
 *
 * 儲存系統設定鍵值對
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('設定鍵');
            $table->text('value')->nullable()->comment('設定值');
            $table->enum('type', ['string', 'integer', 'boolean', 'json'])->default('string')->comment('資料類型');
            $table->string('group', 50)->comment('設定群組');
            $table->text('description')->nullable()->comment('說明');
            $table->timestamps();

            $table->index('group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
