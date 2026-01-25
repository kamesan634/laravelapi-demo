<?php

namespace App\Http\Requests\CustomerLevel;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增會員等級請求驗證
 */
class StoreCustomerLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level_code' => ['required', 'integer', 'min:1', 'unique:customer_levels,level_code'],
            'name' => ['required', 'string', 'max:50'],
            'spending_threshold' => ['required', 'numeric', 'min:0'],
            'maintain_threshold' => ['nullable', 'numeric', 'min:0'],
            'discount_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'points_multiplier' => ['required', 'numeric', 'min:0'],
            'benefits' => ['nullable', 'array'],
            'status' => ['sometimes', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'level_code.required' => '等級代碼為必填欄位',
            'level_code.unique' => '此等級代碼已存在',
            'name.required' => '等級名稱為必填欄位',
            'spending_threshold.required' => '累積消費門檻為必填欄位',
            'discount_rate.required' => '折扣率為必填欄位',
        ];
    }
}
