<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 入庫單 Model
 *
 * 管理各種入庫作業
 */
class GoodsReceipt extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'goods_receipts';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'receipt_no',
        'warehouse_id',
        'receipt_date',
        'receipt_type',
        'source_type',
        'source_id',
        'source_no',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'receipt_date' => 'date',
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
     * 取得入庫明細
     */
    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class, 'receipt_id');
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
