<?php

namespace App\Http\Requests\NumberSequence;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增編號規則請求驗證
 */
class StoreNumberSequenceRequest extends FormRequest
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
            'type' => ['required', 'string', 'max:50', 'unique:number_sequences,type', 'regex:/^[A-Z_]+$/'],
            'prefix' => ['nullable', 'string', 'max:20'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'current_number' => ['integer', 'min:0'],
            'padding' => ['integer', 'min:1', 'max:10'],
            'reset_period' => ['in:never,daily,monthly,yearly'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * 取得驗證錯誤的自訂訊息
     */
    public function messages(): array
    {
        return [
            'type.required' => '編號類型為必填',
            'type.unique' => '編號類型已存在',
            'type.regex' => '編號類型只能包含大寫英文字母和底線',
        ];
    }
}
