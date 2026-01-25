<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 採購單明細 Model
 *
 * 記錄採購單中的商品明細
 */
class PurchaseOrderItem extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'purchase_order_items';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'po_id',
        'product_id',
        'variant_id',
        'quantity',
        'received_quantity',
        'unit_price',
        'discount_rate',
        'line_total',
        'notes',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'quantity' => 'integer',
        'received_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    /**
     * 取得所屬採購單
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
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
    public function receiptItems(): HasMany
    {
        return $this->hasMany(PurchaseReceiptItem::class, 'po_item_id');
    }
}
