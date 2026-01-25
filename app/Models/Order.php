<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 訂單 Model
 *
 * 管理銷售訂單主檔資料
 */
class Order extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'orders';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'order_no',
        'store_id',
        'pos_id',
        'cashier_id',
        'customer_id',
        'order_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'points_earned',
        'points_used',
        'points_amount',
        'status',
        'promotion_ids',
        'coupon_code',
        'notes',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'order_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'points_earned' => 'integer',
        'points_used' => 'integer',
        'points_amount' => 'decimal:2',
        'promotion_ids' => 'array',
    ];

    /**
     * 取得所屬門市
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * 取得所屬客戶
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 取得訂單明細
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * 取得付款紀錄
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * 取得退貨單
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * 取得發票
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * 取得收銀員
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
