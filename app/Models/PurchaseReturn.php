<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 採購退貨單 Model
 *
 * 管理向供應商退貨的作業
 */
class PurchaseReturn extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'purchase_returns';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'return_no',
        'supplier_id',
        'return_date',
        'receipt_id',
        'warehouse_id',
        'return_reason',
        'status',
        'total_amount',
        'notes',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * 取得供應商
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * 取得驗收單
     */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class, 'receipt_id');
    }

    /**
     * 取得出庫倉庫
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * 取得退貨明細
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class, 'return_id');
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
