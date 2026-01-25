<?php

namespace App\Http\Requests\ProductBundle;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增組合商品請求驗證
 */
class StoreProductBundleRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'sku' => ['nullable', 'string', 'max:50', 'unique:product_bundles,sku'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'items' => ['required', 'array', 'min:2'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * 取得驗證錯誤的自訂訊息
     */
    public function messages(): array
    {
        return [
            'name.required' => '組合名稱為必填',
            'sku.unique' => '組合編號已存在',
            'price.required' => '組合價格為必填',
            'items.required' => '組合商品明細為必填',
            'items.min' => '組合商品至少需包含 2 個商品',
            'items.*.product_id.required' => '商品 ID 為必填',
            'items.*.product_id.exists' => '商品不存在',
            'items.*.quantity.required' => '數量為必填',
            'items.*.quantity.min' => '數量至少為 1',
        ];
    }
}
