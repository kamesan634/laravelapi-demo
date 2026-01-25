<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 進貨驗收明細 Model
 *
 * 記錄驗收單中的商品明細
 */
class PurchaseReceiptItem extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'purchase_receipt_items';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'receipt_id',
        'po_item_id',
        'product_id',
        'variant_id',
        'expected_quantity',
        'received_quantity',
        'rejected_quantity',
        'unit_price',
        'line_total',
        'batch_no',
        'expiry_date',
        'quality_status',
        'quality_notes',
    ];

    /**
     * 計算接受數量
     */
    public function getAcceptedQuantityAttribute(): int
    {
        return $this->received_quantity - ($this->rejected_quantity ?? 0);
    }

    /**
     * 屬性轉換
     */
    protected $casts = [
        'expected_quantity' => 'integer',
        'received_quantity' => 'integer',
        'rejected_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    /**
     * 取得所屬驗收單
     */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class, 'receipt_id');
    }

    /**
     * 取得採購明細
     */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'po_item_id');
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
     * 取得退貨明細
     */
    public function returnItems(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class, 'receipt_item_id');
    }
}
