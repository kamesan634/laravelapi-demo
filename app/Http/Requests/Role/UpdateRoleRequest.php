<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 更新角色請求驗證
 */
class UpdateRoleRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($this->role), 'regex:/^[a-zA-Z_]+$/'],
            'display_name' => ['sometimes', 'string', 'max:100'],
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
            'name.unique' => '角色代碼已存在',
            'name.regex' => '角色代碼只能包含英文字母和底線',
            'permission_ids.*.exists' => '選擇的權限不存在',
        ];
    }
}
