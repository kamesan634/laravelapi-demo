<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 暫存訂單明細 Model
 *
 * 記錄暫存訂單中的商品明細
 */
class HoldOrderItem extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'hold_order_items';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'hold_order_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * 取得所屬暫存訂單
     */
    public function holdOrder(): BelongsTo
    {
        return $this->belongsTo(HoldOrder::class);
    }

    /**
     * 取得商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
