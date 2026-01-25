<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增分類請求驗證
 */
class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:20', 'unique:categories,code'],
            'name' => ['required', 'string', 'max:50'],
            'sort_order' => ['integer', 'min:0'],
            'icon' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => '分類代碼為必填欄位',
            'code.unique' => '此分類代碼已存在',
            'name.required' => '分類名稱為必填欄位',
            'parent_id.exists' => '父分類不存在',
        ];
    }
}
