<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 權限 Model
 *
 * 管理系統權限資料
 */
class Permission extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'permissions';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'name',
        'display_name',
        'module',
        'description',
    ];

    /**
     * 取得擁有此權限的角色
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
