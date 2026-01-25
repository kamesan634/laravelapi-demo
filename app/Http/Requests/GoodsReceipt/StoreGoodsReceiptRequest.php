<?php

namespace App\Http\Requests\GoodsReceipt;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增進貨單請求驗證
 */
class StoreGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'receipt_type' => ['required', 'in:PURCHASE,RETURN,TRANSFER,ADJUST,OTHER'],
            'source_type' => ['nullable', 'string', 'max:20'],
            'source_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'receipt_date' => ['required', 'date'],
            'remark' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => '倉庫為必填欄位',
            'receipt_type.required' => '進貨類型為必填欄位',
            'receipt_date.required' => '進貨日期為必填欄位',
            'items.required' => '進貨明細為必填欄位',
            'items.*.product_id.required' => '商品為必填欄位',
            'items.*.quantity.required' => '數量為必填欄位',
        ];
    }
}
