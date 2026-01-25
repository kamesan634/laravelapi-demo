<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 收銀班別 Model
 *
 * 管理收銀員的交班與日結資訊
 */
class CashierShift extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'cashier_shifts';

    /**
     * 關閉 updated_at
     */
    const UPDATED_AT = null;

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'store_id',
        'pos_id',
        'cashier_id',
        'shift_date',
        'start_time',
        'end_time',
        'opening_cash',
        'expected_cash',
        'actual_cash',
        'cash_difference',
        'difference_note',
        'total_sales',
        'total_refunds',
        'total_transactions',
        'status',
        'approved_by',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'shift_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'opening_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_refunds' => 'decimal:2',
        'total_transactions' => 'integer',
    ];

    /**
     * 取得所屬門市
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
     * 取得核准人
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
