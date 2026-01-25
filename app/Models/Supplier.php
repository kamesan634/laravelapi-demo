<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 供應商 Model
 *
 * 管理供應商基本資料與相關關聯
 */
class Supplier extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'suppliers';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'code',
        'name',
        'short_name',
        'tax_id',
        'contact_person',
        'phone',
        'fax',
        'email',
        'address',
        'website',
        'payment_terms',
        'currency',
        'tax_type',
        'min_order_amount',
        'shipping_method',
        'free_shipping_threshold',
        'bank_name',
        'bank_code',
        'bank_branch',
        'account_name',
        'account_number',
        'notes',
        'status',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'min_order_amount' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
    ];

    /**
     * 取得供應商的採購單
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * 取得供應商的驗收單
     */
    public function purchaseReceipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class);
    }

    /**
     * 取得供應商的退貨單
     */
    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    /**
     * 取得供應商的報價
     */
    public function prices(): HasMany
    {
        return $this->hasMany(SupplierPrice::class);
    }

    /**
     * 取得供應商的報價歷史
     */
    public function priceHistory(): HasMany
    {
        return $this->hasMany(SupplierPriceHistory::class);
    }
}
