<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 商品條碼 Model
 *
 * 支援一品多碼的條碼管理
 */
class ProductBarcode extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'product_barcodes';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'product_id',
        'variant_id',
        'barcode',
        'barcode_type',
        'is_primary',
    ];

    /**
     * 屬性預設值
     */
    protected $attributes = [
        'is_primary' => false,
        'barcode_type' => 'EAN13',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'is_primary' => 'boolean',
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
}
