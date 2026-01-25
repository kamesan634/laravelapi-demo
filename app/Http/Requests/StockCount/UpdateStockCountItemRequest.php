<?php

namespace App\Http\Requests\StockCount;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 更新盤點數量請求驗證
 */
class UpdateStockCountItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'counted_quantity' => ['required', 'integer', 'min:0'],
            'remark' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'counted_quantity.required' => '盤點數量為必填欄位',
        ];
    }
}
