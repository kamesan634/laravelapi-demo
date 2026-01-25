<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 操作日誌 Model
 *
 * 記錄系統操作日誌
 */
class AuditLog extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'audit_logs';

    /**
     * 關閉自動更新時間戳
     */
    public $timestamps = false;

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * 取得操作使用者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 取得關聯的模型實例
     */
    public function getModelAttribute(): ?Model
    {
        if (! $this->model_type || ! $this->model_id) {
            return null;
        }

        return $this->model_type::find($this->model_id);
    }

    /**
     * 記錄操作日誌
     */
    public static function log(
        string $action,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): static {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 255),
            'created_at' => now(),
        ]);
    }

    /**
     * 記錄登入日誌
     */
    public static function logLogin(int $userId): static
    {
        return static::create([
            'user_id' => $userId,
            'action' => 'login',
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 255),
            'created_at' => now(),
        ]);
    }

    /**
     * 記錄登出日誌
     */
    public static function logLogout(int $userId): static
    {
        return static::create([
            'user_id' => $userId,
            'action' => 'logout',
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 255),
            'created_at' => now(),
        ]);
    }
}
