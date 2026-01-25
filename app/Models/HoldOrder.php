<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 暫存訂單 Model
 *
 * 管理暫時保留的訂單
 */
class HoldOrder extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'hold_orders';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'hold_number',
        'customer_id',
        'user_id',
        'subtotal',
        'discount_amount',
        'total_amount',
        'notes',
        'held_at',
        'expires_at',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'held_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * 取得暫存訂單明細
     */
    public function items(): HasMany
    {
        return $this->hasMany(HoldOrderItem::class);
    }

    /**
     * 取得客戶
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 取得建立者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 檢查是否已過期
     */
    public function getIsExpiredAttribute(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * 重新計算總金額
     */
    public function recalculate(): void
    {
        $subtotal = $this->items->sum('subtotal');
        $this->subtotal = $subtotal;
        $this->total_amount = $subtotal - $this->discount_amount;
        $this->save();
    }
}
