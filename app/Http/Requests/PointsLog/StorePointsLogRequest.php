<?php

namespace App\Http\Requests\PointsLog;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 調整點數請求驗證
 */
class StorePointsLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:EARN,REDEEM,BONUS,ADJUST,EXPIRE,REFUND'],
            'points' => ['required', 'integer'],
            'description' => ['nullable', 'string', 'max:200'],
            'expire_date' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => '異動類型為必填欄位',
            'type.in' => '異動類型無效',
            'points.required' => '點數為必填欄位',
            'points.integer' => '點數必須為整數',
            'expire_date.after' => '到期日必須是未來日期',
        ];
    }
}
