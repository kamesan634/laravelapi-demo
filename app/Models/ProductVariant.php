<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 商品規格 Model
 *
 * 管理商品的不同規格選項
 */
class ProductVariant extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'product_variants';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'variant_options',
        'cost_price',
        'selling_price',
        'image_url',
        'status',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'variant_options' => 'array',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    /**
     * 取得所屬商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 取得規格的條碼
     */
    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class, 'variant_id');
    }

    /**
     * 取得規格的庫存
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class, 'variant_id');
    }

    /**
     * 取得規格的庫存異動
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'variant_id');
    }

    /**
     * 取得規格的訂單明細
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }
}
