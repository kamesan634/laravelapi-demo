<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 商品分類 Model
 *
 * 階層式商品分類管理
 */
class Category extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'categories';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'code',
        'name',
        'parent_id',
        'level',
        'path',
        'sort_order',
        'icon',
        'status',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'level' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * 取得父分類
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * 取得子分類
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * 取得分類下的商品
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * 取得所有子孫分類（遞迴）
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * 取得所有祖先分類（遞迴）
     */
    public function ancestors(): BelongsTo
    {
        return $this->parent()->with('ancestors');
    }
}
