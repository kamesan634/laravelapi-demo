<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增商品請求驗證
 */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:30', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:30', 'unique:products,barcode'],
            'name' => ['required', 'string', 'max:200'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'unit' => ['required', 'string', 'max:10'],
            'cost_price' => ['numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
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
            'sku.required' => '商品編號為必填欄位',
            'sku.unique' => '此商品編號已存在',
            'name.required' => '商品名稱為必填欄位',
            'category_id.required' => '商品分類為必填欄位',
            'unit.required' => '單位為必填欄位',
            'selling_price.required' => '售價為必填欄位',
        ];
    }
}
