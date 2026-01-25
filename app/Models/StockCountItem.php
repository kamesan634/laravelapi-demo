<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 盤點明細 Model
 *
 * 記錄盤點單中各商品的盤點結果
 */
class StockCountItem extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'stock_count_items';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'count_id',
        'product_id',
        'variant_id',
        'system_quantity',
        'counted_quantity',
        'variance_quantity',
        'unit_cost',
        'variance_amount',
        'item_status',
        'notes',
        'counted_by',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'system_quantity' => 'integer',
        'counted_quantity' => 'integer',
        'variance_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'variance_amount' => 'decimal:2',
    ];

    /**
     * 取得所屬盤點單
     */
    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class, 'count_id');
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
     * 取得盤點人
     */
    public function counter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }
}
