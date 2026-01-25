<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 暫存訂單明細資料表遷移
 *
 * 記錄暫存訂單中的商品明細
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hold_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hold_order_id')->constrained('hold_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('quantity')->comment('數量');
            $table->decimal('unit_price', 12, 2)->comment('單價');
            $table->decimal('subtotal', 12, 2)->comment('小計');
            $table->timestamps();

            $table->index('hold_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hold_order_items');
    }
};
