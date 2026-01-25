<?php

namespace App\Http\Requests\CashierShift;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 開班請求驗證
 */
class StoreCashierShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'exists:stores,id'],
            'register_no' => ['required', 'string', 'max:20'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'remark' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_id.required' => '門市為必填欄位',
            'store_id.exists' => '門市不存在',
            'register_no.required' => '收銀機編號為必填欄位',
            'opening_cash.required' => '期初現金為必填欄位',
            'opening_cash.min' => '期初現金不能為負數',
        ];
    }
}
