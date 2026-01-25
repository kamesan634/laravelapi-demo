<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 入庫單明細 Model
 *
 * 記錄入庫單中的商品明細
 */
class GoodsReceiptItem extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'goods_receipt_items';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'receipt_id',
        'product_id',
        'variant_id',
        'expected_quantity',
        'received_quantity',
        'unit_cost',
        'total_cost',
        'batch_no',
        'expiry_date',
        'notes',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'expected_quantity' => 'integer',
        'received_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    /**
     * 取得所屬入庫單
     */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'receipt_id');
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
