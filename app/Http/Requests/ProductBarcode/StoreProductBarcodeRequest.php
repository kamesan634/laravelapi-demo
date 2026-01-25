<?php

namespace App\Http\Requests\ProductBarcode;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 新增商品條碼請求驗證
 */
class StoreProductBarcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barcode' => ['required', 'string', 'max:50', 'unique:product_barcodes,barcode'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'barcode_type' => ['in:EAN13,EAN8,UPCA,CODE39,CODE128,QRCODE'],
            'is_primary' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'barcode.required' => '條碼為必填欄位',
            'barcode.unique' => '此條碼已存在',
        ];
    }
}
