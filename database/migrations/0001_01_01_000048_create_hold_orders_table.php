<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 暫存訂單資料表遷移
 *
 * 儲存暫時保留的訂單
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hold_orders', function (Blueprint $table) {
            $table->id();
            $table->string('hold_number', 50)->unique()->comment('暫存單號');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('subtotal', 12, 2)->default(0)->comment('小計');
            $table->decimal('discount_amount', 12, 2)->default(0)->comment('折扣金額');
            $table->decimal('total_amount', 12, 2)->default(0)->comment('總金額');
            $table->text('notes')->nullable()->comment('備註');
            $table->timestamp('held_at')->comment('暫存時間');
            $table->timestamp('expires_at')->nullable()->comment('過期時間');
            $table->timestamps();

            $table->index('user_id');
            $table->index('customer_id');
            $table->index('held_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hold_orders');
    }
};
