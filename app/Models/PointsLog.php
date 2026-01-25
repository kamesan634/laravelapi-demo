<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 點數紀錄 Model
 *
 * 記錄會員點數的增減變動
 */
class PointsLog extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'points_logs';

    /**
     * 關閉 updated_at
     */
    const UPDATED_AT = null;

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'customer_id',
        'type',
        'points',
        'balance',
        'reference_type',
        'reference_id',
        'description',
        'expire_date',
        'created_by',
        'created_at',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'points' => 'integer',
        'balance' => 'integer',
        'expire_date' => 'date',
    ];

    /**
     * 取得所屬會員
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 取得建立者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
