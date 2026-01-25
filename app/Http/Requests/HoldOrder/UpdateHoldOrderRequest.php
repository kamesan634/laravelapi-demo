<?php

namespace App\Http\Requests\HoldOrder;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 更新暫存訂單請求驗證
 */
class UpdateHoldOrderRequest extends FormRequest
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
            'customer_id' => ['nullable', 'exists:customers,id'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'expires_hours' => ['nullable', 'integer', 'min:1', 'max:72'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'exists:products,id'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
        ];
    }

    /**
     * 取得驗證錯誤的自訂訊息
     */
    public function messages(): array
    {
        return [
            'customer_id.exists' => '客戶不存在',
            'items.min' => '訂單至少需包含 1 個商品',
            'items.*.product_id.exists' => '商品不存在',
            'items.*.quantity.min' => '數量至少為 1',
        ];
    }
}
