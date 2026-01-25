<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 編號規則資料表遷移
 *
 * 管理各類單據的自動編號規則
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->unique()->comment('編號類型');
            $table->string('prefix', 20)->nullable()->comment('前綴');
            $table->string('suffix', 20)->nullable()->comment('後綴');
            $table->integer('current_number')->default(0)->comment('目前編號');
            $table->integer('padding')->default(6)->comment('編號位數');
            $table->enum('reset_period', ['never', 'daily', 'monthly', 'yearly'])->default('never')->comment('重置週期');
            $table->timestamp('last_reset_at')->nullable()->comment('上次重置時間');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
