<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增倉庫請求驗證
 */
class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:STORE,WAREHOUSE'],
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
            'code.required' => '倉庫代碼為必填欄位',
            'code.unique' => '此倉庫代碼已存在',
            'name.required' => '倉庫名稱為必填欄位',
            'type.required' => '倉庫類型為必填欄位',
        ];
    }
}
