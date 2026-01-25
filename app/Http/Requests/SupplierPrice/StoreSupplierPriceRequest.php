<?php

namespace App\Http\Requests\SupplierPrice;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增供應商報價請求驗證
 */
class StoreSupplierPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'min_quantity' => ['nullable', 'numeric', 'min:1'],
            'supplier_sku' => ['nullable', 'string', 'max:100'],
            'lead_days' => ['nullable', 'integer', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after:effective_from'],
            'is_primary' => ['boolean'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => '供應商為必填欄位',
            'product_id.required' => '商品為必填欄位',
            'unit_price.required' => '單價為必填欄位',
            'effective_from.required' => '生效日期為必填欄位',
            'effective_to.after' => '失效日期必須在生效日期之後',
        ];
    }
}
