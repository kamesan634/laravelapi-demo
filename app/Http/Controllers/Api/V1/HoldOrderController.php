<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\HoldOrder\StoreHoldOrderRequest;
use App\Http\Requests\HoldOrder\UpdateHoldOrderRequest;
use App\Models\HoldOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 暫存訂單控制器
 *
 * 處理暫存訂單的 CRUD 操作
 */
class HoldOrderController extends Controller
{
    use ApiResponse;

    /**
     * 取得暫存訂單列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = HoldOrder::with(['customer', 'user', 'items.product']);

        // 使用者篩選（收銀員只能看到自己的暫存單）
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // 客戶篩選
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // 只顯示未過期的
        if ($request->boolean('active_only', true)) {
            $query->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
        }

        // 排序
        $query->orderBy('held_at', 'desc');

        $holdOrders = $query->paginate($request->input('per_page', 15));

        return $this->paginated($holdOrders, '查詢暫存訂單列表成功');
    }

    /**
     * 新增暫存訂單
     */
    public function store(StoreHoldOrderRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // 產生暫存單號
            $holdNumber = 'HOLD'.date('YmdHis').str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

            // 計算過期時間
            $expiresAt = null;
            if ($request->filled('expires_hours')) {
                $expiresAt = Carbon::now()->addHours($request->expires_hours);
            }

            // 計算金額
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['unit_price'] * $item['quantity'];
            }
            $discountAmount = $request->input('discount_amount', 0);
            $totalAmount = $subtotal - $discountAmount;

            // 建立暫存訂單
            $holdOrder = HoldOrder::create([
                'hold_number' => $holdNumber,
                'customer_id' => $request->customer_id,
                'user_id' => auth()->id(),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
                'held_at' => now(),
                'expires_at' => $expiresAt,
            ]);

            // 建立訂單明細
            foreach ($request->items as $item) {
                $holdOrder->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['unit_price'] * $item['quantity'],
                ]);
            }

            $holdOrder->load(['customer', 'user', 'items.product']);

            DB::commit();

            return $this->created($holdOrder, '暫存訂單建立成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('暫存訂單建立失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得暫存訂單詳情
     */
    public function show(HoldOrder $holdOrder): JsonResponse
    {
        $holdOrder->load(['customer', 'user', 'items.product']);

        $data = $holdOrder->toArray();
        $data['is_expired'] = $holdOrder->is_expired;

        return $this->success($data, '取得暫存訂單詳情成功');
    }

    /**
     * 更新暫存訂單
     */
    public function update(UpdateHoldOrderRequest $request, HoldOrder $holdOrder): JsonResponse
    {
        // 檢查是否已過期
        if ($holdOrder->is_expired) {
            return $this->error('此暫存訂單已過期', 422);
        }

        try {
            DB::beginTransaction();

            // 更新過期時間
            $expiresAt = $holdOrder->expires_at;
            if ($request->filled('expires_hours')) {
                $expiresAt = Carbon::now()->addHours($request->expires_hours);
            }

            // 更新基本資料
            $holdOrder->update([
                'customer_id' => $request->input('customer_id', $holdOrder->customer_id),
                'discount_amount' => $request->input('discount_amount', $holdOrder->discount_amount),
                'notes' => $request->input('notes', $holdOrder->notes),
                'expires_at' => $expiresAt,
            ]);

            // 如果有更新明細，重新建立
            if ($request->has('items')) {
                $holdOrder->items()->delete();

                $subtotal = 0;
                foreach ($request->items as $item) {
                    $holdOrder->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['unit_price'] * $item['quantity'],
                    ]);
                    $subtotal += $item['unit_price'] * $item['quantity'];
                }

                $holdOrder->subtotal = $subtotal;
                $holdOrder->total_amount = $subtotal - $holdOrder->discount_amount;
                $holdOrder->save();
            }

            $holdOrder->load(['customer', 'user', 'items.product']);

            DB::commit();

            return $this->success($holdOrder, '暫存訂單更新成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('暫存訂單更新失敗：'.$e->getMessage());
        }
    }

    /**
     * 刪除暫存訂單
     */
    public function destroy(HoldOrder $holdOrder): JsonResponse
    {
        try {
            $holdOrder->delete();

            return $this->success(null, '暫存訂單刪除成功');
        } catch (\Exception $e) {
            return $this->serverError('暫存訂單刪除失敗：'.$e->getMessage());
        }
    }

    /**
     * 還原為正式訂單
     */
    public function restore(Request $request, HoldOrder $holdOrder): JsonResponse
    {
        // 檢查是否已過期
        if ($holdOrder->is_expired) {
            return $this->error('此暫存訂單已過期，無法還原', 422);
        }

        try {
            DB::beginTransaction();

            // 產生訂單編號
            $orderNo = 'SO'.date('YmdHis').str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

            // 建立正式訂單
            $order = Order::create([
                'order_no' => $orderNo,
                'store_id' => $request->input('store_id'),
                'cashier_id' => auth()->id(),
                'customer_id' => $holdOrder->customer_id,
                'order_date' => now(),
                'subtotal' => $holdOrder->subtotal,
                'discount_amount' => $holdOrder->discount_amount,
                'tax_amount' => 0,
                'total_amount' => $holdOrder->total_amount,
                'status' => 'PENDING',
                'notes' => $holdOrder->notes,
            ]);

            // 複製訂單明細
            foreach ($holdOrder->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'sku' => $item->product->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'original_price' => $item->unit_price,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'subtotal' => $item->subtotal,
                    'cost_price' => $item->product->cost_price,
                ]);
            }

            // 刪除暫存訂單
            $holdOrder->delete();

            $order->load(['customer', 'items.product']);

            DB::commit();

            return $this->success($order, '已成功還原為正式訂單');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('還原訂單失敗：'.$e->getMessage());
        }
    }
}
