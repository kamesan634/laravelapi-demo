<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 庫存調整單 Model
 *
 * 管理庫存數量的手動調整作業
 */
class StockAdjustment extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'stock_adjustments';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'adjustment_no',
        'warehouse_id',
        'adjustment_date',
        'adjustment_type',
        'source_type',
        'source_id',
        'product_id',
        'variant_id',
        'before_quantity',
        'adjust_quantity',
        'after_quantity',
        'unit_cost',
        'adjustment_value',
        'status',
        'reason',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'adjustment_date' => 'date',
        'before_quantity' => 'integer',
        'adjust_quantity' => 'integer',
        'after_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'adjustment_value' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * 取得所屬倉庫
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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
     * 取得審核人
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * 取得建立者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
