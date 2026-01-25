<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增角色請求驗證
 */
class StoreRoleRequest extends FormRequest
{
    /**
     * 判斷使用者是否有權限進行此請求
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 取得適用於請求的驗證規則
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'unique:roles,name', 'regex:/^[a-zA-Z_]+$/'],
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    /**
     * 取得驗證錯誤的自訂訊息
     */
    public function messages(): array
    {
        return [
            'name.required' => '角色代碼為必填',
            'name.unique' => '角色代碼已存在',
            'name.regex' => '角色代碼只能包含英文字母和底線',
            'display_name.required' => '顯示名稱為必填',
            'permission_ids.*.exists' => '選擇的權限不存在',
        ];
    }
}
