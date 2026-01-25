<?php

namespace App\Http\Requests\StockCount;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增盤點單請求驗證
 */
class StoreStockCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'count_type' => ['required', 'in:FULL,PARTIAL,CYCLE,SPOT'],
            'count_date' => ['required', 'date'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'remark' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => '倉庫為必填欄位',
            'count_type.required' => '盤點類型為必填欄位',
            'count_date.required' => '盤點日期為必填欄位',
        ];
    }
}
