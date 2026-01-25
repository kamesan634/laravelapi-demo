<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 進貨驗收單 Model
 *
 * 管理採購訂單的到貨驗收作業
 */
class PurchaseReceipt extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'purchase_receipts';

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'receipt_no',
        'po_id',
        'supplier_id',
        'receipt_date',
        'warehouse_id',
        'status',
        'total_amount',
        'supplier_invoice_no',
        'supplier_invoice_date',
        'notes',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'receipt_date' => 'date',
        'total_amount' => 'decimal:2',
        'supplier_invoice_date' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * 取得採購單
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

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
     * 取得驗收明細
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReceiptItem::class, 'receipt_id');
    }

    /**
     * 取得退貨單
     */
    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class, 'receipt_id');
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
