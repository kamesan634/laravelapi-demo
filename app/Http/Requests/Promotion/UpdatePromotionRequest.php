<?php

namespace App\Http\Requests\Promotion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 更新促銷活動請求驗證
 */
class UpdatePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:30', Rule::unique('promotions', 'code')->ignore($this->promotion)],
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'promotion_type' => ['sometimes', 'string', 'max:30'],
            'start_time' => ['sometimes', 'date'],
            'end_time' => ['sometimes', 'date', 'after_or_equal:start_time'],
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
            'conditions' => ['sometimes', 'array'],
            'discount_rules' => ['sometimes', 'array'],
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
            'code.unique' => '此促銷代碼已存在',
            'end_time.after_or_equal' => '結束時間必須晚於或等於開始時間',
        ];
    }
}
