<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 採購退貨明細 Model
 *
 * 記錄採購退貨單中的商品明細
 */
class PurchaseReturnItem extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'purchase_return_items';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'return_id',
        'product_id',
        'variant_id',
        'receipt_item_id',
        'quantity',
        'unit_price',
        'line_total',
        'batch_no',
        'reason',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    /**
     * 取得所屬退貨單
     */
    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class, 'return_id');
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
     * 取得驗收明細
     */
    public function receiptItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceiptItem::class, 'receipt_item_id');
    }
}
