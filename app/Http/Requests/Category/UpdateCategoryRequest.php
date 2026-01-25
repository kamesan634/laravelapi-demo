<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 更新分類請求驗證
 */
class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'exists:categories,id'],
            'code' => ['sometimes', 'string', 'max:20', Rule::unique('categories', 'code')->ignore($this->category)],
            'name' => ['sometimes', 'string', 'max:50'],
            'sort_order' => ['integer', 'min:0'],
            'icon' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => '此分類代碼已存在',
            'parent_id.exists' => '父分類不存在',
        ];
    }
}
