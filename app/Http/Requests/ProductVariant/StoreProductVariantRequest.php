<?php

namespace App\Http\Requests\ProductVariant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增商品規格請求驗證
 */
class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:50', 'unique:product_variants,sku'],
            'barcode' => ['nullable', 'string', 'max:30', 'unique:product_variants,barcode'],
            'variant_options' => ['required', 'array'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'status' => ['in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'SKU 為必填欄位',
            'sku.unique' => '此 SKU 已存在',
            'variant_options.required' => '規格組合為必填欄位',
        ];
    }
}
