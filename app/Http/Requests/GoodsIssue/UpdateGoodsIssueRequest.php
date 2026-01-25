<?php

namespace App\Http\Requests\GoodsIssue;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 更新出貨單請求驗證
 */
class UpdateGoodsIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'issue_date' => ['sometimes', 'date'],
            'remark' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
