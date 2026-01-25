<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 組合商品資料表遷移
 *
 * 管理組合商品主檔
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('組合名稱');
            $table->string('sku', 50)->unique()->nullable()->comment('組合編號');
            $table->text('description')->nullable()->comment('描述');
            $table->decimal('price', 12, 2)->comment('組合價格');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_bundles');
    }
};
