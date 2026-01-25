<?php

namespace App\Http\Requests\PurchaseReceipt;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 更新採購收貨單請求驗證
 */
class UpdatePurchaseReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'remark' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
