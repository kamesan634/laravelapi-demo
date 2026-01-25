<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 更新倉庫請求驗證
 */
class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:20', Rule::unique('warehouses', 'code')->ignore($this->warehouse)],
            'name' => ['sometimes', 'string', 'max:100'],
            'type' => ['sometimes', 'in:STORE,WAREHOUSE'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'address' => ['nullable', 'string', 'max:200'],
            'contact_person' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_default' => ['boolean'],
            'status' => ['sometimes', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => '此倉庫代碼已存在',
        ];
    }
}
