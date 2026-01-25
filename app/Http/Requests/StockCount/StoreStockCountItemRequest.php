<?php

namespace App\Http\Requests\StockCount;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增盤點項目請求驗證
 */
class StoreStockCountItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'counted_quantity' => ['required', 'integer', 'min:0'],
            'remark' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => '商品為必填欄位',
            'counted_quantity.required' => '盤點數量為必填欄位',
        ];
    }
}
