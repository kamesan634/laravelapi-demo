<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 會員等級 Model
 *
 * 管理會員等級與相關優惠設定
 */
class CustomerLevel extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'customer_levels';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'level_code',
        'name',
        'spending_threshold',
        'maintain_threshold',
        'discount_rate',
        'points_multiplier',
        'benefits',
        'status',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'level_code' => 'integer',
        'spending_threshold' => 'decimal:2',
        'maintain_threshold' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'points_multiplier' => 'decimal:1',
        'benefits' => 'array',
    ];

    /**
     * 取得該等級的會員
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'level_id');
    }
}
