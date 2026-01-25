<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 付款控制器
 *
 * 處理訂單付款的新增與查詢操作
 */
class PaymentController extends Controller
{
    use ApiResponse;

    /**
     * 查詢訂單付款紀錄
     *
     * @param  Order  $order  訂單
     */
    public function index(Request $request, Order $order): JsonResponse
    {
        $payments = $order->payments()->orderBy('created_at', 'desc')->get();

        return $this->success($payments, '查詢付款紀錄成功');
    }

    /**
     * 新增付款
     *
     * @param  StorePaymentRequest  $request  付款請求
     * @param  Order  $order  訂單
     */
    public function store(StorePaymentRequest $request, Order $order): JsonResponse
    {
        // 檢查訂單狀態（只有已完成的訂單可以記錄付款）
        if ($order->status !== 'COMPLETED') {
            return $this->error('此訂單狀態無法進行付款', 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->validated();

            // 計算已付金額
            $paidAmount = $order->payments()->where('status', 'SUCCESS')->sum('amount');
            $remainingAmount = $order->total_amount - $paidAmount;

            if ($data['amount'] > $remainingAmount) {
                return $this->error('付款金額超過應付金額', 422);
            }

            // 建立付款紀錄
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => $data['payment_method'],
                'amount' => $data['amount'],
                'received_amount' => $data['received_amount'] ?? $data['amount'],
                'change_amount' => $data['change_amount'] ?? 0,
                'card_last_four' => $data['card_last_four'] ?? null,
                'auth_code' => $data['auth_code'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'status' => 'SUCCESS',
            ]);

            DB::commit();

            return $this->created([
                'payment' => $payment,
                'order' => $order,
            ], '付款成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('付款失敗：'.$e->getMessage());
        }
    }
}
