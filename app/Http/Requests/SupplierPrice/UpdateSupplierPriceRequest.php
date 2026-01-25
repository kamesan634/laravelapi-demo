<?php

namespace App\Http\Requests\SupplierPrice;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 更新供應商報價請求驗證
 */
class UpdateSupplierPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
            'min_quantity' => ['nullable', 'numeric', 'min:1'],
            'supplier_sku' => ['nullable', 'string', 'max:100'],
            'lead_days' => ['nullable', 'integer', 'min:0'],
            'effective_from' => ['sometimes', 'date'],
            'effective_to' => ['nullable', 'date', 'after:effective_from'],
            'is_primary' => ['boolean'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'effective_to.after' => '失效日期必須在生效日期之後',
        ];
    }
}
