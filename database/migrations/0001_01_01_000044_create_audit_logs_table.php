<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 操作日誌資料表遷移
 *
 * 記錄系統操作日誌，用於稽核追蹤
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action', ['create', 'update', 'delete', 'view', 'login', 'logout'])->comment('操作類型');
            $table->string('model_type', 100)->nullable()->comment('模型類型');
            $table->unsignedBigInteger('model_id')->nullable()->comment('模型 ID');
            $table->json('old_values')->nullable()->comment('變更前的值');
            $table->json('new_values')->nullable()->comment('變更後的值');
            $table->string('ip_address', 45)->nullable()->comment('IP 位址');
            $table->string('user_agent', 255)->nullable()->comment('瀏覽器資訊');
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index(['model_type', 'model_id']);
            $table->index('created_at');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
