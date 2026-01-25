<?php

/**
 * 供應商資料表 Migration
 *
 * 儲存供應商基本資料與交易條件
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行 Migration
     * 建立 suppliers 資料表
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            // 主鍵
            $table->id();

            // 基本資訊
            $table->string('code', 20)->unique()->comment('供應商編號');
            $table->string('name', 100)->comment('供應商名稱');
            $table->string('short_name', 50)->nullable()->comment('供應商簡稱');
            $table->string('tax_id', 10)->nullable()->comment('統一編號');

            // 聯絡資訊
            $table->string('contact_person', 50)->nullable()->comment('聯絡人');
            $table->string('phone', 20)->nullable()->comment('電話');
            $table->string('fax', 20)->nullable()->comment('傳真');
            $table->string('email', 100)->nullable()->comment('Email');
            $table->string('address', 200)->nullable()->comment('地址');
            $table->string('website', 200)->nullable()->comment('網站');

            // 交易條件
            $table->enum('payment_terms', ['CASH', 'COD', 'NET30', 'NET60', 'NET90'])->default('NET30')->comment('付款條件');
            $table->string('currency', 3)->default('TWD')->comment('幣別');
            $table->string('tax_type', 10)->default('TAX')->comment('稅別');
            $table->decimal('min_order_amount', 12, 2)->nullable()->comment('最低訂購金額');
            $table->enum('shipping_method', ['FREE', 'FIXED', 'BY_AMOUNT', 'BY_WEIGHT'])->nullable()->comment('運費計算方式');
            $table->decimal('free_shipping_threshold', 12, 2)->nullable()->comment('免運門檻');

            // 銀行資訊
            $table->string('bank_name', 50)->nullable()->comment('銀行名稱');
            $table->string('bank_code', 10)->nullable()->comment('銀行代碼');
            $table->string('bank_branch', 50)->nullable()->comment('分行名稱');
            $table->string('account_name', 100)->nullable()->comment('帳戶名稱');
            $table->string('account_number', 20)->nullable()->comment('帳戶號碼');

            // 其他
            $table->text('notes')->nullable()->comment('備註');

            // 狀態
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->comment('狀態');

            // 時間戳記
            $table->timestamps();

            // 索引
            $table->index('code');
            $table->index('status');
        });
    }

    /**
     * 回滾 Migration
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
