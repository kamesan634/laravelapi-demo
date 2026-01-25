<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointsLog;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 訂單控制器
 *
 * 處理銷售訂單的 CRUD 操作及狀態變更
 */
class OrderController extends Controller
{
    use ApiResponse;

    /**
     * 取得訂單列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['store', 'customer', 'cashier', 'items']);

        // 搜尋條件（訂單編號、會員資訊）
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('order_no', 'like', "%{$keyword}%")
                    ->orWhereHas('customer', function ($cq) use ($keyword) {
                        $cq->where('name', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%");
                    });
            });
        }

        // 門市篩選
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // 會員篩選
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // 訂單狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('order_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('order_date', '<=', $request->end_date);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $orders = $query->paginate($perPage);

        return $this->paginated($orders, '查詢訂單列表成功');
    }

    /**
     * 新增訂單
     *
     * @param  StoreOrderRequest  $request  新增請求
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // 生成訂單編號（格式：SO + 日期 + 流水號）
            $orderNo = 'SO'.date('Ymd').str_pad(
                Order::whereDate('created_at', today())->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            // 計算訂單金額
            $subtotal = 0;
            $discountAmount = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = $item['quantity'];
                $unitPrice = $item['unit_price'];
                $originalPrice = $item['original_price'];
                $itemDiscount = $item['discount_amount'] ?? 0;
                $itemSubtotal = ($unitPrice * $quantity) - $itemDiscount;

                $subtotal += $originalPrice * $quantity;
                $discountAmount += $itemDiscount;

                $itemsData[] = [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'original_price' => $originalPrice,
                    'discount_amount' => $itemDiscount,
                    'tax_amount' => 0,
                    'subtotal' => $itemSubtotal,
                    'cost_price' => $product->cost_price ?? 0,
                ];
            }

            // 計算稅額（假設 5% 營業稅）
            $taxRate = 0.05;
            $taxAmount = round(($subtotal - $discountAmount) * $taxRate, 2);
            $totalAmount = $subtotal - $discountAmount + $taxAmount;

            // 處理會員點數
            $pointsUsed = $data['points_used'] ?? 0;
            $pointsAmount = 0;

            if (! empty($data['customer_id']) && $pointsUsed > 0) {
                $customer = Customer::find($data['customer_id']);
                if ($customer && $customer->available_points >= $pointsUsed) {
                    // 假設 1 點 = 1 元
                    $pointsAmount = $pointsUsed;
                    $totalAmount -= $pointsAmount;
                } else {
                    return $this->error('會員點數不足', 422);
                }
            }

            // 建立訂單
            $order = Order::create([
                'order_no' => $orderNo,
                'store_id' => $data['store_id'],
                'pos_id' => $data['pos_id'] ?? null,
                'cashier_id' => auth()->id(),
                'customer_id' => $data['customer_id'] ?? null,
                'order_date' => now(),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'points_earned' => 0,
                'points_used' => $pointsUsed,
                'points_amount' => $pointsAmount,
                'status' => 'COMPLETED',
                'promotion_ids' => $data['promotion_ids'] ?? null,
                'coupon_code' => $data['coupon_code'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // 建立訂單明細
            foreach ($itemsData as $itemData) {
                $itemData['order_id'] = $order->id;
                OrderItem::create($itemData);
            }

            // 扣除會員點數
            if ($pointsUsed > 0 && ! empty($data['customer_id'])) {
                $customer = Customer::find($data['customer_id']);
                $customer->available_points -= $pointsUsed;
                $customer->save();

                // 記錄點數變動
                PointsLog::create([
                    'customer_id' => $customer->id,
                    'type' => 'REDEEM',
                    'points' => -$pointsUsed,
                    'balance' => $customer->available_points,
                    'reference_type' => 'ORDER',
                    'reference_id' => $order->id,
                    'description' => "訂單 {$orderNo} 折抵點數",
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();

            $order->load(['store', 'customer', 'items', 'cashier']);

            return $this->created($order, '訂單建立成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('訂單建立失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得單一訂單
     *
     * @param  Order  $order  訂單
     */
    public function show(Order $order): JsonResponse
    {
        $order->load(['store', 'customer', 'items.product', 'items.variant', 'payments', 'cashier', 'invoice']);

        return $this->success($order, '取得訂單詳情成功');
    }

    /**
     * 更新訂單
     *
     * @param  UpdateOrderRequest  $request  更新請求
     * @param  Order  $order  訂單
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        // 只允許更新待處理狀態的訂單
        if ($order->status !== 'PENDING') {
            return $this->error('只能更新待處理狀態的訂單', 422);
        }

        $order->update($request->validated());
        $order->load(['store', 'customer', 'items', 'cashier']);

        return $this->success($order, '訂單更新成功');
    }

    /**
     * 刪除訂單
     *
     * @param  Order  $order  訂單
     */
    public function destroy(Order $order): JsonResponse
    {
        // 只允許刪除待處理或已取消的訂單
        if (! in_array($order->status, ['PENDING', 'CANCELLED'])) {
            return $this->error('只能刪除待處理或已取消的訂單', 422);
        }

        // 檢查是否有付款
        if ($order->payments()->exists()) {
            return $this->error('此訂單有付款紀錄，無法刪除', 422);
        }

        try {
            DB::beginTransaction();

            // 如果有使用點數，退還點數
            if ($order->points_used > 0 && $order->customer_id) {
                $customer = Customer::find($order->customer_id);
                if ($customer) {
                    $customer->available_points += $order->points_used;
                    $customer->save();

                    PointsLog::create([
                        'customer_id' => $customer->id,
                        'type' => 'ADJUST',
                        'points' => $order->points_used,
                        'balance' => $customer->available_points,
                        'reference_type' => 'ORDER',
                        'reference_id' => $order->id,
                        'description' => "訂單 {$order->order_no} 刪除退還點數",
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            $order->items()->delete();
            $order->delete();

            DB::commit();

            return $this->success(null, '訂單刪除成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('訂單刪除失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得訂單明細
     *
     * @param  Order  $order  訂單
     */
    public function items(Order $order): JsonResponse
    {
        $items = $order->items()->with(['product', 'variant'])->get();

        return $this->success($items, '取得訂單明細成功');
    }

    /**
     * 取消訂單
     *
     * @param  Order  $order  訂單
     */
    public function cancel(Order $order): JsonResponse
    {
        // 只允許取消已完成的訂單
        if ($order->status !== 'COMPLETED') {
            return $this->error('只能取消已完成狀態的訂單', 422);
        }

        try {
            DB::beginTransaction();

            // 如果有使用點數，退還點數
            if ($order->points_used > 0 && $order->customer_id) {
                $customer = Customer::find($order->customer_id);
                if ($customer) {
                    $customer->available_points += $order->points_used;
                    $customer->save();

                    PointsLog::create([
                        'customer_id' => $customer->id,
                        'type' => 'ADJUST',
                        'points' => $order->points_used,
                        'balance' => $customer->available_points,
                        'reference_type' => 'ORDER',
                        'reference_id' => $order->id,
                        'description' => "訂單 {$order->order_no} 取消退還點數",
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            $order->status = 'VOIDED';
            $order->save();

            DB::commit();

            return $this->success($order, '訂單取消成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('訂單取消失敗：'.$e->getMessage());
        }
    }

    /**
     * 完成訂單
     *
     * @param  Order  $order  訂單
     */
    public function complete(Order $order): JsonResponse
    {
        // 檢查訂單狀態
        if ($order->status !== 'PAID') {
            return $this->error('只能完成已付款的訂單', 422);
        }

        try {
            DB::beginTransaction();

            $order->status = 'COMPLETED';
            $order->save();

            // 計算並給予會員點數（假設消費金額 1:1 點數）
            if ($order->customer_id) {
                $customer = Customer::find($order->customer_id);
                if ($customer) {
                    $pointsEarned = (int) floor($order->total_amount);

                    $customer->available_points += $pointsEarned;
                    $customer->total_points += $pointsEarned;
                    $customer->save();

                    $order->points_earned = $pointsEarned;
                    $order->save();

                    PointsLog::create([
                        'customer_id' => $customer->id,
                        'type' => 'EARN',
                        'points' => $pointsEarned,
                        'balance' => $customer->available_points,
                        'reference_type' => 'ORDER',
                        'reference_id' => $order->id,
                        'description' => "訂單 {$order->order_no} 消費獲得點數",
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            $order->load(['store', 'customer', 'items']);

            return $this->success($order, '訂單完成成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('訂單完成失敗：'.$e->getMessage());
        }
    }
}
