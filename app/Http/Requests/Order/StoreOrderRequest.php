<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增訂單請求驗證
 */
class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'exists:stores,id'],
            'pos_id' => ['nullable', 'string', 'max:20'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.original_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'promotion_ids' => ['nullable', 'array'],
            'promotion_ids.*' => ['exists:promotions,id'],
            'coupon_code' => ['nullable', 'string', 'max:30'],
            'points_used' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_id.required' => '門市為必填欄位',
            'store_id.exists' => '門市不存在',
            'items.required' => '訂單明細為必填欄位',
            'items.min' => '至少需要一項商品',
            'items.*.product_id.required' => '商品為必填欄位',
            'items.*.quantity.required' => '數量為必填欄位',
            'items.*.quantity.min' => '數量至少為 1',
            'items.*.unit_price.required' => '單價為必填欄位',
            'items.*.original_price.required' => '原價為必填欄位',
        ];
    }
}
