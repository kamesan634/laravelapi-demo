<?php

namespace App\Http\Requests\PurchaseReturn;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增採購退貨單請求驗證
 */
class StorePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_receipt_id' => ['nullable', 'exists:purchase_receipts,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'return_date' => ['required', 'date'],
            'return_reason' => ['required', 'in:QUALITY,DAMAGED,WRONG_ITEM,EXPIRED,OVER_DELIVERY,OTHER'],
            'reason_detail' => ['required', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => '供應商為必填欄位',
            'warehouse_id.required' => '倉庫為必填欄位',
            'return_date.required' => '退貨日期為必填欄位',
            'return_reason.required' => '退貨原因為必填欄位',
            'reason_detail.required' => '原因說明為必填欄位',
            'items.required' => '退貨明細為必填欄位',
            'items.*.product_id.required' => '商品為必填欄位',
            'items.*.quantity.required' => '數量為必填欄位',
            'items.*.unit_price.required' => '單價為必填欄位',
        ];
    }
}
