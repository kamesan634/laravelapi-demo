<?php

namespace App\Http\Requests\ProductVariant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 更新商品規格請求驗證
 */
class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['sometimes', 'string', 'max:50', Rule::unique('product_variants', 'sku')->ignore($this->variant)],
            'barcode' => ['nullable', 'string', 'max:30', Rule::unique('product_variants', 'barcode')->ignore($this->variant)],
            'variant_options' => ['sometimes', 'array'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'status' => ['in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => '此 SKU 已存在',
        ];
    }
}
