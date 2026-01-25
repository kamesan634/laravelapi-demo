<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 門市 Model
 *
 * 管理門市基本資料與相關關聯
 */
class Store extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'stores';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'code',
        'name',
        'short_name',
        'phone',
        'fax',
        'email',
        'address',
        'business_hours',
        'manager',
        'status',
    ];

    /**
     * 取得門市的倉庫
     */
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    /**
     * 取得門市的訂單
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * 取得門市的收銀班別
     */
    public function cashierShifts(): HasMany
    {
        return $this->hasMany(CashierShift::class);
    }
}
