<?php

/**
 * 庫存異動資料表 Migration
 *
 * 記錄所有庫存異動的歷史紀錄
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 inventory_movements 資料表
     */
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 關聯
            $table->foreignId('product_id')->constrained('products')->comment('商品ID');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->comment('規格ID');
            $table->foreignId('warehouse_id')->constrained('warehouses')->comment('倉庫ID');

            // 異動類型
            $table->enum('movement_type', [
                'PURCHASE_IN',   // 採購入庫
                'SALES_OUT',     // 銷售出庫
                'RETURN_IN',     // 退貨入庫
                'RETURN_OUT',    // 退貨出庫
                'TRANSFER_IN',   // 調撥入庫
                'TRANSFER_OUT',  // 調撥出庫
                'ADJUST_IN',     // 調整入庫
                'ADJUST_OUT',    // 調整出庫
                'COUNT_IN',      // 盤盈入庫
                'COUNT_OUT',     // 盤虧出庫
                'SCRAP_OUT',     // 報廢出庫
                'OTHER_IN',      // 其他入庫
                'OTHER_OUT',      // 其他出庫
            ])->comment('異動類型');

            // 數量資訊
            $table->integer('quantity')->comment('異動數量');
            $table->integer('before_quantity')->comment('異動前庫存');
            $table->integer('after_quantity')->comment('異動後庫存');

            // 成本
            $table->decimal('unit_cost', 12, 2)->nullable()->comment('單位成本');

            // 關聯單據
            $table->string('reference_type', 20)->nullable()->comment('來源類型');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('來源ID');
            $table->string('reference_no', 30)->nullable()->comment('來源單號');

            // 備註
            $table->string('notes', 200)->nullable()->comment('備註');

            // 時間戳記
            $table->timestamp('created_at')->useCurrent();
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('建立者');

            // 索引
            $table->index(['product_id', 'warehouse_id']);
            $table->index('movement_type');
            $table->index('created_at');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
