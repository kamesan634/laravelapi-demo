<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增付款請求驗證
 */
class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'received_amount' => ['nullable', 'numeric', 'min:0'],
            'change_amount' => ['nullable', 'numeric', 'min:0'],
            'card_last_four' => ['nullable', 'string', 'max:4'],
            'auth_code' => ['nullable', 'string', 'max:20'],
            'reference_no' => ['nullable', 'string', 'max:50'],
            'status' => ['in:SUCCESS,FAILED,REFUNDED'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => '付款方式為必填欄位',
            'amount.required' => '金額為必填欄位',
            'amount.min' => '金額必須大於 0',
        ];
    }
}
