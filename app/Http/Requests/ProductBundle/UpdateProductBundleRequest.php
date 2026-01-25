<?php

namespace App\Http\Requests\ProductBundle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 更新組合商品請求驗證
 */
class UpdateProductBundleRequest extends FormRequest
{
    /**
     * 判斷使用者是否有權限進行此請求
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 取得適用於請求的驗證規則
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('product_bundles', 'sku')->ignore($this->product_bundle)],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'items' => ['sometimes', 'array', 'min:2'],
            'items.*.product_id' => ['required_with:items', 'exists:products,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
        ];
    }

    /**
     * 取得驗證錯誤的自訂訊息
     */
    public function messages(): array
    {
        return [
            'sku.unique' => '組合編號已存在',
            'items.min' => '組合商品至少需包含 2 個商品',
            'items.*.product_id.exists' => '商品不存在',
            'items.*.quantity.min' => '數量至少為 1',
        ];
    }
}
