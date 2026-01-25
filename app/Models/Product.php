<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 商品 Model
 *
 * 管理商品主檔資料與相關關聯
 */
class Product extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'products';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'short_name',
        'category_id',
        'brand',
        'description',
        'unit',
        'cost_price',
        'selling_price',
        'member_price',
        'min_price',
        'tax_type',
        'safety_stock',
        'max_stock',
        'track_inventory',
        'supplier_id',
        'image_url',
        'notes',
        'status',
        'created_by',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'member_price' => 'decimal:2',
        'min_price' => 'decimal:2',
        'safety_stock' => 'integer',
        'max_stock' => 'integer',
        'track_inventory' => 'boolean',
    ];

    /**
     * 取得商品分類
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 取得主要供應商
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * 取得建立者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 取得商品規格
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * 取得商品條碼
     */
    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    /**
     * 取得商品庫存
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * 取得商品庫存異動
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * 取得訂單明細
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * 取得供應商報價
     */
    public function supplierPrices(): HasMany
    {
        return $this->hasMany(SupplierPrice::class);
    }
}
