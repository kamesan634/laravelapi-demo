<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 付款紀錄 Model
 *
 * 記錄訂單的付款資訊
 */
class Payment extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'payments';

    /**
     * 關閉 updated_at
     */
    const UPDATED_AT = null;

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'order_id',
        'payment_method',
        'amount',
        'received_amount',
        'change_amount',
        'card_last_four',
        'auth_code',
        'reference_no',
        'status',
        'created_at',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    /**
     * 取得所屬訂單
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
