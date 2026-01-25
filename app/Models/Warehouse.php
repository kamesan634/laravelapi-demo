<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 倉庫 Model
 *
 * 管理倉庫基本資料與庫存關聯
 */
class Warehouse extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'warehouses';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'store_id',
        'address',
        'contact_person',
        'phone',
        'is_default',
        'status',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * 取得所屬門市
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * 取得倉庫的庫存
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * 取得倉庫的庫存異動
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * 取得倉庫的入庫單
     */
    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    /**
     * 取得倉庫的出庫單
     */
    public function goodsIssues(): HasMany
    {
        return $this->hasMany(GoodsIssue::class);
    }

    /**
     * 取得倉庫的盤點單
     */
    public function stockCounts(): HasMany
    {
        return $this->hasMany(StockCount::class);
    }

    /**
     * 取得調出的調撥單
     */
    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_id');
    }

    /**
     * 取得調入的調撥單
     */
    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_id');
    }
}
