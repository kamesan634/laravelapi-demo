<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 更新密碼請求驗證
 */
class UpdatePasswordRequest extends FormRequest
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
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * 取得驗證錯誤的自訂訊息
     */
    public function messages(): array
    {
        return [
            'current_password.required' => '目前密碼為必填欄位',
            'current_password.current_password' => '目前密碼不正確',
            'password.required' => '新密碼為必填欄位',
            'password.min' => '新密碼至少需要 8 個字元',
            'password.confirmed' => '新密碼確認不相符',
        ];
    }
}
