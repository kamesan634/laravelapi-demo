<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 退貨單 Model
 *
 * 管理退貨/退款作業
 */
class Refund extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'refunds';

    /**
     * 關閉 updated_at
     */
    const UPDATED_AT = null;

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'refund_no',
        'order_id',
        'store_id',
        'cashier_id',
        'approver_id',
        'refund_date',
        'refund_type',
        'reason_code',
        'reason_note',
        'refund_amount',
        'refund_method',
        'points_deducted',
        'status',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'refund_date' => 'datetime',
        'refund_amount' => 'decimal:2',
        'points_deducted' => 'integer',
    ];

    /**
     * 取得原訂單
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 取得退貨明細
     */
    public function items(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }

    /**
     * 取得門市
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * 取得收銀員
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * 取得審核人
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
