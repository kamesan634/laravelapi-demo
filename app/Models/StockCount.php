<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 盤點單 Model
 *
 * 管理庫存盤點作業
 */
class StockCount extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'stock_counts';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'count_no',
        'warehouse_id',
        'count_date',
        'count_type',
        'category_id',
        'status',
        'total_items',
        'variance_items',
        'variance_amount',
        'notes',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'count_date' => 'date',
        'total_items' => 'integer',
        'variance_items' => 'integer',
        'variance_amount' => 'decimal:2',
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
     * 取得盤點分類
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * 取得盤點明細
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockCountItem::class, 'count_id');
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
