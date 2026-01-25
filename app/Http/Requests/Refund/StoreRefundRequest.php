<?php

namespace App\Http\Requests\Refund;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增退貨單請求驗證
 */
class StoreRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'exists:orders,id'],
            'store_id' => ['required', 'exists:stores,id'],
            'refund_type' => ['required', 'in:REFUND,EXCHANGE,REFUND_ONLY'],
            'reason_code' => ['required', 'string', 'max:20'],
            'reason_note' => ['nullable', 'string'],
            'refund_method' => ['required', 'string', 'max:20'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'exists:order_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.refund_amount' => ['required', 'numeric', 'min:0'],
            'items.*.reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => '原始訂單為必填欄位',
            'order_id.exists' => '原始訂單不存在',
            'store_id.required' => '門市為必填欄位',
            'refund_type.required' => '退貨類型為必填欄位',
            'reason_code.required' => '退貨原因碼為必填欄位',
            'refund_method.required' => '退款方式為必填欄位',
            'items.required' => '退貨明細為必填欄位',
            'items.*.order_item_id.required' => '原始訂單項目為必填',
            'items.*.quantity.required' => '退貨數量為必填欄位',
        ];
    }
}
