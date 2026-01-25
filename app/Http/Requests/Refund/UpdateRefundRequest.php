<?php

namespace App\Http\Requests\Refund;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 更新退貨單請求驗證
 */
class UpdateRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason_note' => ['sometimes', 'string'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
