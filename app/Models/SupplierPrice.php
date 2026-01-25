<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 供應商報價 Model
 *
 * 管理各供應商對商品的報價資訊
 */
class SupplierPrice extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'supplier_prices';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'supplier_id',
        'product_id',
        'variant_id',
        'unit_price',
        'min_quantity',
        'supplier_sku',
        'lead_days',
        'effective_from',
        'effective_to',
        'is_primary',
        'is_active',
        'notes',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'unit_price' => 'decimal:2',
        'min_quantity' => 'decimal:2',
        'lead_days' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

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
     * 取得報價歷史
     */
    public function history(): HasMany
    {
        return $this->hasMany(SupplierPriceHistory::class, 'supplier_price_id');
    }

    /**
     * 取得報價歷史 (複數別名)
     */
    public function histories(): HasMany
    {
        return $this->history();
    }

    /**
     * 檢查報價是否有效
     */
    public function isValid(): bool
    {
        $now = now()->toDateString();

        return $this->is_active
            && $now >= $this->effective_from
            && ($this->effective_to === null || $now <= $this->effective_to);
    }
}
