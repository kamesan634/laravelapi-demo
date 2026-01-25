<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 組合商品 Model
 *
 * 管理組合商品主檔
 */
class ProductBundle extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'product_bundles';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'name',
        'sku',
        'description',
        'price',
        'is_active',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * 取得組合商品明細
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductBundleItem::class, 'bundle_id');
    }

    /**
     * 取得組合中的商品
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_bundle_items', 'bundle_id', 'product_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * 計算組合商品的原價（各商品售價總和）
     */
    public function getOriginalPriceAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->product->selling_price * $item->quantity;
        });
    }

    /**
     * 計算折扣金額
     */
    public function getDiscountAmountAttribute(): float
    {
        return max(0, $this->original_price - $this->price);
    }
}
