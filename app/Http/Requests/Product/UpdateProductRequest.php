<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 更新商品請求驗證
 */
class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['sometimes', 'string', 'max:30', Rule::unique('products', 'sku')->ignore($this->product)],
            'barcode' => ['nullable', 'string', 'max:30', Rule::unique('products', 'barcode')->ignore($this->product)],
            'name' => ['sometimes', 'string', 'max:200'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'brand' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'unit' => ['sometimes', 'string', 'max:10'],
            'cost_price' => ['numeric', 'min:0'],
            'selling_price' => ['sometimes', 'numeric', 'min:0'],
            'member_price' => ['nullable', 'numeric', 'min:0'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'tax_type' => ['in:TAX,TAX_FREE,ZERO_TAX'],
            'safety_stock' => ['integer', 'min:0'],
            'max_stock' => ['nullable', 'integer', 'min:0'],
            'track_inventory' => ['boolean'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
            'status' => ['in:ACTIVE,INACTIVE,DISCONTINUED'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => '此商品編號已存在',
        ];
    }
}
