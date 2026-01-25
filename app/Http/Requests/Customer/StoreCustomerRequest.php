<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增會員請求驗證
 */
class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_no' => ['required', 'string', 'max:20', 'unique:customers,member_no'],
            'name' => ['required', 'string', 'max:50'],
            'gender' => ['nullable', 'in:M,F,OTHER'],
            'birthday' => ['nullable', 'date'],
            'phone' => ['required', 'string', 'max:20', 'unique:customers,phone'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:200'],
            'level_id' => ['nullable', 'exists:customer_levels,id'],
            'join_date' => ['required', 'date'],
            'join_store_id' => ['nullable', 'exists:stores,id'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_no.required' => '會員編號為必填欄位',
            'member_no.unique' => '此會員編號已存在',
            'name.required' => '姓名為必填欄位',
            'phone.required' => '手機號碼為必填欄位',
            'phone.unique' => '此手機號碼已被註冊',
            'join_date.required' => '加入日期為必填欄位',
            'email.email' => '請輸入有效的電子郵件格式',
        ];
    }
}
