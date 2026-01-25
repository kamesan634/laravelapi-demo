<?php

/**
 * 發票資料表 Migration
 *
 * 儲存銷售發票資訊
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 invoices 資料表
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 發票基本資訊
            $table->string('invoice_no', 12)->unique()->comment('發票號碼');
            $table->foreignId('order_id')->constrained('orders')->comment('訂單ID');
            $table->date('invoice_date')->comment('發票日期');

            // 發票類型
            $table->enum('invoice_type', ['B2C', 'B2C_CARRIER', 'B2C_DONATE', 'B2B'])->comment('發票類型');

            // 買方資訊（三聯式）
            $table->string('buyer_tax_id', 8)->nullable()->comment('買方統編');
            $table->string('buyer_name', 100)->nullable()->comment('買方名稱');

            // 載具資訊
            $table->string('carrier_type', 20)->nullable()->comment('載具類型');
            $table->string('carrier_no', 20)->nullable()->comment('載具號碼');
            $table->string('donate_code', 10)->nullable()->comment('愛心碼');

            // 金額
            $table->decimal('sales_amount', 12, 2)->comment('銷售額');
            $table->decimal('tax_amount', 12, 2)->comment('稅額');
            $table->decimal('total_amount', 12, 2)->comment('總計');

            // 列印狀態
            $table->boolean('print_flag')->default(false)->comment('是否已列印');

            // 作廢資訊
            $table->boolean('void_flag')->default(false)->comment('是否作廢');
            $table->date('void_date')->nullable()->comment('作廢日期');
            $table->string('void_reason', 200)->nullable()->comment('作廢原因');

            // 時間戳記
            $table->timestamp('created_at')->useCurrent();

            // 索引
            $table->index('invoice_no');
            $table->index('order_id');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
