<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 庫存 Model
 *
 * 記錄各倉庫的商品庫存數量
 */
class Inventory extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'inventory';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'product_id',
        'variant_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'last_count_date',
        'last_movement_date',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'last_count_date' => 'date',
        'last_movement_date' => 'datetime',
    ];

    /**
     * 取得所屬商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 取得所屬規格
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * 取得所屬倉庫
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * 取得可用庫存
     */
    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }
}
