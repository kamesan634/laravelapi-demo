<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 促銷活動 Model
 *
 * 管理各種促銷活動與折扣規則
 */
class Promotion extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'promotions';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'promotion_type',
        'start_time',
        'end_time',
        'applicable_products',
        'applicable_categories',
        'excluded_products',
        'applicable_stores',
        'applicable_member_levels',
        'conditions',
        'discount_rules',
        'usage_limit_per_customer',
        'total_usage_limit',
        'current_usage',
        'stackable',
        'priority',
        'status',
        'created_by',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
        'excluded_products' => 'array',
        'applicable_stores' => 'array',
        'applicable_member_levels' => 'array',
        'conditions' => 'array',
        'discount_rules' => 'array',
        'usage_limit_per_customer' => 'integer',
        'total_usage_limit' => 'integer',
        'current_usage' => 'integer',
        'stackable' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * 取得建立者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 檢查促銷是否有效
     */
    public function isValid(): bool
    {
        $now = now();

        return $this->status === 'ACTIVE'
            && $now->between($this->start_time, $this->end_time)
            && ($this->total_usage_limit === null || $this->current_usage < $this->total_usage_limit);
    }
}
