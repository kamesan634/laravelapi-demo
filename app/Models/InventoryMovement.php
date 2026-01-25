<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 庫存異動 Model
 *
 * 記錄所有庫存異動的歷史紀錄
 */
class InventoryMovement extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'inventory_movements';

    /**
     * 關閉 updated_at
     */
    const UPDATED_AT = null;

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'product_id',
        'variant_id',
        'warehouse_id',
        'movement_type',
        'quantity',
        'before_quantity',
        'after_quantity',
        'unit_cost',
        'reference_type',
        'reference_id',
        'reference_no',
        'notes',
        'created_by',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'quantity' => 'integer',
        'before_quantity' => 'integer',
        'after_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
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
     * 取得建立者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
