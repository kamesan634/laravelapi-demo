<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 訂單明細 Model
 *
 * 記錄訂單中的商品明細
 */
class OrderItem extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'order_items';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'product_name',
        'sku',
        'quantity',
        'unit_price',
        'original_price',
        'discount_amount',
        'tax_amount',
        'subtotal',
        'cost_price',
        'promotion_id',
        'notes',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'cost_price' => 'decimal:2',
    ];

    /**
     * 取得所屬訂單
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 取得商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 取得商品規格
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * 取得退貨明細
     */
    public function refundItems(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }
}
