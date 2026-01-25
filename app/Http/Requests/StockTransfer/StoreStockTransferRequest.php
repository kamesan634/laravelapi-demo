<?php

namespace App\Http\Requests\StockTransfer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增調撥單請求驗證
 */
class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'transfer_date' => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:transfer_date'],
            'remark' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_warehouse_id.required' => '來源倉庫為必填欄位',
            'to_warehouse_id.required' => '目的倉庫為必填欄位',
            'to_warehouse_id.different' => '目的倉庫不能與來源倉庫相同',
            'transfer_date.required' => '調撥日期為必填欄位',
            'items.required' => '調撥明細為必填欄位',
            'items.*.product_id.required' => '商品為必填欄位',
            'items.*.quantity.required' => '數量為必填欄位',
        ];
    }
}
