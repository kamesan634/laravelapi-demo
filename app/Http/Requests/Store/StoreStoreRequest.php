<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增門市請求驗證
 */
class StoreStoreRequest extends FormRequest
{
    /**
     * 判斷使用者是否有權限發送此請求
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 取得適用於此請求的驗證規則
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:stores,code'],
            'name' => ['required', 'string', 'max:100'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'fax' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:200'],
            'business_hours' => ['nullable', 'string', 'max:100'],
            'manager' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'in:ACTIVE,INACTIVE'],
        ];
    }

    /**
     * 取得驗證錯誤的自訂訊息
     */
    public function messages(): array
    {
        return [
            'code.required' => '門市代碼為必填欄位',
            'code.unique' => '此門市代碼已存在',
            'name.required' => '門市名稱為必填欄位',
        ];
    }
}
