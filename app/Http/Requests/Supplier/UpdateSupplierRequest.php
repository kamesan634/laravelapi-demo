<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 更新供應商請求驗證
 */
class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:20', Rule::unique('suppliers', 'code')->ignore($this->supplier)],
            'name' => ['sometimes', 'string', 'max:100'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'tax_id' => ['nullable', 'string', 'max:10'],
            'contact_person' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'fax' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:200'],
            'website' => ['nullable', 'string', 'max:200'],
            'payment_terms' => ['in:CASH,COD,NET30,NET60,NET90'],
            'currency' => ['string', 'max:3'],
            'tax_type' => ['in:TAX,TAX_FREE,ZERO_TAX'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_method' => ['nullable', 'in:FREE,FIXED,BY_AMOUNT,BY_WEIGHT'],
            'free_shipping_threshold' => ['nullable', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:50'],
            'bank_code' => ['nullable', 'string', 'max:10'],
            'bank_branch' => ['nullable', 'string', 'max:50'],
            'account_name' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'status' => ['in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => '此供應商編號已存在',
        ];
    }
}
