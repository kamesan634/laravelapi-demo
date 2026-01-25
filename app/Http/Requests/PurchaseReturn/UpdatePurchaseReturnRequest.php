<?php

namespace App\Http\Requests\PurchaseReturn;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 更新採購退貨單請求驗證
 */
class UpdatePurchaseReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason_detail' => ['sometimes', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
