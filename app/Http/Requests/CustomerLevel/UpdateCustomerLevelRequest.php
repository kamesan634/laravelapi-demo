<?php

namespace App\Http\Requests\CustomerLevel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 更新會員等級請求驗證
 */
class UpdateCustomerLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level_code' => ['sometimes', 'integer', 'min:1', Rule::unique('customer_levels', 'level_code')->ignore($this->customer_level)],
            'name' => ['sometimes', 'string', 'max:50'],
            'spending_threshold' => ['sometimes', 'numeric', 'min:0'],
            'maintain_threshold' => ['nullable', 'numeric', 'min:0'],
            'discount_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'points_multiplier' => ['sometimes', 'numeric', 'min:0'],
            'benefits' => ['nullable', 'array'],
            'status' => ['sometimes', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'level_code.unique' => '此等級代碼已存在',
        ];
    }
}
