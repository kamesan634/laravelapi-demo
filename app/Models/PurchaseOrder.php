<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 採購單 Model
 *
 * 管理採購訂單主檔資料
 */
class PurchaseOrder extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'purchase_orders';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'po_no',
        'supplier_id',
        'order_date',
        'expected_date',
        'warehouse_id',
        'status',
        'subtotal',
        'tax_amount',
        'total_amount',
        'payment_terms',
        'notes',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * 取得供應商
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * 取得入庫倉庫
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * 取得採購明細
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'po_id');
    }

    /**
     * 取得驗收單
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(PurchaseReceipt::class, 'po_id');
    }

    /**
     * 取得審核人
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * 取得建立者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
