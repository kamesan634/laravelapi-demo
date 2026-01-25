<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 出庫單明細 Model
 *
 * 記錄出庫單中的商品明細
 */
class GoodsIssueItem extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'goods_issue_items';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'issue_id',
        'product_id',
        'variant_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'batch_no',
        'notes',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    /**
     * 取得所屬出庫單
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(GoodsIssue::class, 'issue_id');
    }

    /**
     * 取得商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 取得規格
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
