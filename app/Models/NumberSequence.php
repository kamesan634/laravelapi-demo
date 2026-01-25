<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 編號規則 Model
 *
 * 管理各類單據的自動編號規則
 */
class NumberSequence extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'number_sequences';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'type',
        'prefix',
        'suffix',
        'current_number',
        'padding',
        'reset_period',
        'last_reset_at',
        'is_active',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'current_number' => 'integer',
        'padding' => 'integer',
        'is_active' => 'boolean',
        'last_reset_at' => 'datetime',
    ];
}
