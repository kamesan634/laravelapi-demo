<?php

namespace App\Http\Requests\GoodsIssue;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增出貨單請求驗證
 */
class StoreGoodsIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'issue_type' => ['required', 'in:SALES,RETURN,TRANSFER,ADJUST,SCRAP,OTHER'],
            'source_type' => ['nullable', 'string', 'max:20'],
            'source_id' => ['nullable', 'integer'],
            'issue_date' => ['required', 'date'],
            'remark' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => '倉庫為必填欄位',
            'issue_type.required' => '出貨類型為必填欄位',
            'issue_date.required' => '出貨日期為必填欄位',
            'items.required' => '出貨明細為必填欄位',
            'items.*.product_id.required' => '商品為必填欄位',
            'items.*.quantity.required' => '數量為必填欄位',
        ];
    }
}
