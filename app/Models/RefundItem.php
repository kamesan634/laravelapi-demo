<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 退貨明細 Model
 *
 * 記錄退貨單中的商品明細
 */
class RefundItem extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'refund_items';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'refund_id',
        'order_item_id',
        'product_id',
        'quantity',
        'unit_price',
        'refund_amount',
        'return_to_stock',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'return_to_stock' => 'boolean',
    ];

    /**
     * 取得所屬退貨單
     */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    /**
     * 取得原訂單明細
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * 取得商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
