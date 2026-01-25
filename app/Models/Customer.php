<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 客戶/會員 Model
 *
 * 管理客戶與會員資料
 */
class Customer extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'customers';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'member_no',
        'name',
        'gender',
        'birthday',
        'phone',
        'email',
        'address',
        'level_id',
        'total_spending',
        'total_points',
        'available_points',
        'join_date',
        'join_store_id',
        'notes',
        'status',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'birthday' => 'date',
        'join_date' => 'date',
        'total_points' => 'integer',
        'available_points' => 'integer',
        'total_spending' => 'decimal:2',
    ];

    /**
     * 取得會員等級
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(CustomerLevel::class, 'level_id');
    }

    /**
     * 取得加入門市
     */
    public function joinStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'join_store_id');
    }

    /**
     * 取得會員的訂單
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * 取得會員的點數紀錄
     */
    public function pointsLogs(): HasMany
    {
        return $this->hasMany(PointsLog::class);
    }
}
