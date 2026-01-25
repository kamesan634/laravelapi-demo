<?php

namespace App\Http\Requests\StockAdjustment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增庫存調整單請求驗證
 */
class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'adjustment_date' => ['required', 'date'],
            'adjustment_type' => ['required', 'in:COUNT,DAMAGE,EXPIRED,CORRECTION,OTHER'],
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'adjust_quantity' => ['required', 'integer'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => '倉庫為必填欄位',
            'adjustment_date.required' => '調整日期為必填欄位',
            'adjustment_type.required' => '調整類型為必填欄位',
            'product_id.required' => '商品為必填欄位',
            'adjust_quantity.required' => '調整數量為必填欄位',
            'reason.required' => '調整原因為必填欄位',
        ];
    }
}
