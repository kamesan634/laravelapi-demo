<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 組合商品明細 Model
 *
 * 記錄組合商品包含的商品
 */
class ProductBundleItem extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'product_bundle_items';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'bundle_id',
        'product_id',
        'quantity',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * 取得所屬組合商品
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(ProductBundle::class, 'bundle_id');
    }

    /**
     * 取得商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
