<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 更新會員請求驗證
 */
class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_no' => ['sometimes', 'string', 'max:20', Rule::unique('customers', 'member_no')->ignore($this->customer)],
            'name' => ['sometimes', 'string', 'max:50'],
            'gender' => ['nullable', 'in:M,F,OTHER'],
            'birthday' => ['nullable', 'date'],
            'phone' => ['sometimes', 'string', 'max:20', Rule::unique('customers', 'phone')->ignore($this->customer)],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:200'],
            'level_id' => ['nullable', 'exists:customer_levels,id'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_no.unique' => '此會員編號已存在',
            'phone.unique' => '此手機號碼已被註冊',
            'email.email' => '請輸入有效的電子郵件格式',
        ];
    }
}
