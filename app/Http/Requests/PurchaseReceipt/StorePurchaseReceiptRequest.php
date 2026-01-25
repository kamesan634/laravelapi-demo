<?php

namespace App\Http\Requests\PurchaseReceipt;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增採購收貨單請求驗證
 */
class StorePurchaseReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'receipt_date' => ['required', 'date'],
            'remark' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.po_item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.received_quantity' => ['required', 'integer', 'min:1'],
            'items.*.accepted_quantity' => ['required', 'integer', 'min:0'],
            'items.*.rejected_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.remark' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'purchase_order_id.required' => '採購單為必填欄位',
            'warehouse_id.required' => '倉庫為必填欄位',
            'receipt_date.required' => '收貨日期為必填欄位',
            'items.required' => '收貨明細為必填欄位',
            'items.*.po_item_id.required' => '採購單項目為必填',
            'items.*.received_quantity.required' => '收貨數量為必填欄位',
            'items.*.accepted_quantity.required' => '驗收數量為必填欄位',
        ];
    }
}
