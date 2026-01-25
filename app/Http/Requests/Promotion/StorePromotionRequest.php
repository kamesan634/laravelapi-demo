<?php

namespace App\Http\Requests\Promotion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增促銷活動請求驗證
 */
class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'unique:promotions,code'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'promotion_type' => ['required', 'string', 'max:30'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after_or_equal:start_time'],
            'applicable_products' => ['nullable', 'array'],
            'applicable_products.*' => ['exists:products,id'],
            'applicable_categories' => ['nullable', 'array'],
            'applicable_categories.*' => ['exists:categories,id'],
            'excluded_products' => ['nullable', 'array'],
            'excluded_products.*' => ['exists:products,id'],
            'applicable_stores' => ['nullable', 'array'],
            'applicable_stores.*' => ['exists:stores,id'],
            'applicable_member_levels' => ['nullable', 'array'],
            'applicable_member_levels.*' => ['exists:customer_levels,id'],
            'conditions' => ['required', 'array'],
            'discount_rules' => ['required', 'array'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:0'],
            'total_usage_limit' => ['nullable', 'integer', 'min:0'],
            'stackable' => ['boolean'],
            'priority' => ['integer', 'min:0'],
            'status' => ['in:DRAFT,ACTIVE,INACTIVE,EXPIRED'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => '促銷代碼為必填欄位',
            'code.unique' => '此促銷代碼已存在',
            'name.required' => '促銷名稱為必填欄位',
            'promotion_type.required' => '促銷類型為必填欄位',
            'conditions.required' => '促銷條件為必填欄位',
            'discount_rules.required' => '折扣規則為必填欄位',
            'start_time.required' => '開始時間為必填欄位',
            'end_time.required' => '結束時間為必填欄位',
            'end_time.after_or_equal' => '結束時間必須晚於或等於開始時間',
        ];
    }
}
