<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 角色 Model
 *
 * 管理系統角色資料
 */
class Role extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'roles';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_active',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * 取得角色的權限
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    /**
     * 取得擁有此角色的使用者
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * 檢查角色是否擁有指定權限
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * 賦予角色權限
     */
    public function givePermissions(array|int $permissionIds): void
    {
        $this->permissions()->syncWithoutDetaching((array) $permissionIds);
    }

    /**
     * 移除角色權限
     */
    public function revokePermissions(array|int $permissionIds): void
    {
        $this->permissions()->detach((array) $permissionIds);
    }

    /**
     * 同步角色權限
     */
    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }
}
