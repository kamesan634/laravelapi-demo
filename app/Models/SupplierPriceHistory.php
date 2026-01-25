<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 供應商報價歷史 Model
 *
 * 記錄供應商報價的變更歷史
 */
class SupplierPriceHistory extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'supplier_price_history';

    /**
     * 關閉 updated_at
     */
    const UPDATED_AT = null;

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'supplier_price_id',
        'supplier_id',
        'product_id',
        'variant_id',
        'old_price',
        'new_price',
        'price_change',
        'change_percentage',
        'change_reason',
        'effective_date',
        'created_by',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'price_change' => 'decimal:2',
        'change_percentage' => 'decimal:2',
        'effective_date' => 'date',
    ];

    /**
     * 取得報價
     */
    public function supplierPrice(): BelongsTo
    {
        return $this->belongsTo(SupplierPrice::class, 'supplier_price_id');
    }

    /**
     * 取得供應商
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * 取得商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 取得規格
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * 取得建立者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
