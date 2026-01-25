<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 調撥明細 Model
 *
 * 記錄調撥單中的商品明細
 */
class StockTransferItem extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'stock_transfer_items';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'transfer_id',
        'product_id',
        'variant_id',
        'quantity',
        'shipped_quantity',
        'received_quantity',
        'unit_cost',
        'batch_no',
        'notes',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'quantity' => 'integer',
        'shipped_quantity' => 'integer',
        'received_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    /**
     * 取得所屬調撥單
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
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
}
