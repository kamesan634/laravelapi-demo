<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 發票 Model
 *
 * 管理銷售發票資訊
 */
class Invoice extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'invoices';

    /**
     * 關閉 updated_at
     */
    const UPDATED_AT = null;

    /**
     * 可批量賦值的欄位
     */
    protected $fillable = [
        'invoice_no',
        'order_id',
        'invoice_date',
        'invoice_type',
        'buyer_tax_id',
        'buyer_name',
        'carrier_type',
        'carrier_no',
        'donate_code',
        'sales_amount',
        'tax_amount',
        'total_amount',
        'print_flag',
        'void_flag',
        'void_date',
        'void_reason',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'invoice_date' => 'date',
        'sales_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'print_flag' => 'boolean',
        'void_flag' => 'boolean',
        'void_date' => 'date',
    ];

    /**
     * 取得所屬訂單
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
