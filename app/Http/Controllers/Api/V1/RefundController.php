<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Refund\StoreRefundRequest;
use App\Http\Requests\Refund\UpdateRefundRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointsLog;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 退貨控制器
 *
 * 處理退貨單的 CRUD 操作及審核流程
 */
class RefundController extends Controller
{
    use ApiResponse;

    /**
     * 取得退貨單列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Refund::with(['order', 'order.customer', 'store', 'cashier', 'approver']);

        // 搜尋條件
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('refund_no', 'like', "%{$keyword}%")
                    ->orWhereHas('order', function ($oq) use ($keyword) {
                        $oq->where('order_no', 'like', "%{$keyword}%");
                    });
            });
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('refund_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('refund_date', '<=', $request->end_date);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $refunds = $query->paginate($perPage);

        return $this->paginated($refunds, '查詢退貨單列表成功');
    }

    /**
     * 新增退貨單
     *
     * @param  StoreRefundRequest  $request  新增請求
     */
    public function store(StoreRefundRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // 取得原訂單
            $order = Order::findOrFail($data['order_id']);

            // 驗證訂單狀態（必須是已完成的訂單才能退貨）
            if ($order->status !== 'COMPLETED') {
                return $this->error('此訂單狀態無法進行退貨', 422);
            }

            // 生成退貨單編號
            $refundNo = 'RF'.date('Ymd').str_pad(
                Refund::whereDate('created_at', today())->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            // 計算退貨總金額
            $totalRefundAmount = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $orderItem = OrderItem::findOrFail($item['order_item_id']);

                // 驗證退貨數量
                $alreadyRefunded = RefundItem::where('order_item_id', $orderItem->id)
                    ->whereHas('refund', function ($q) {
                        $q->where('status', 'COMPLETED');
                    })
                    ->sum('quantity');

                $availableQty = $orderItem->quantity - $alreadyRefunded;

                if ($item['quantity'] > $availableQty) {
                    return $this->error("商品 {$orderItem->product_name} 可退數量不足", 422);
                }

                $totalRefundAmount += $item['refund_amount'];

                $itemsData[] = [
                    'order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'variant_id' => $orderItem->variant_id,
                    'product_name' => $orderItem->product_name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $orderItem->unit_price,
                    'refund_amount' => $item['refund_amount'],
                    'reason' => $item['reason'] ?? null,
                ];
            }

            // 建立退貨單
            $refund = Refund::create([
                'refund_no' => $refundNo,
                'order_id' => $order->id,
                'store_id' => $data['store_id'],
                'cashier_id' => auth()->id(),
                'refund_date' => now(),
                'refund_type' => $data['refund_type'],
                'reason_code' => $data['reason_code'],
                'reason_note' => $data['reason_note'] ?? null,
                'refund_amount' => $totalRefundAmount,
                'refund_method' => $data['refund_method'],
                'points_deducted' => 0,
                'status' => 'COMPLETED',
            ]);

            // 建立退貨明細
            foreach ($itemsData as $itemData) {
                $itemData['refund_id'] = $refund->id;
                RefundItem::create($itemData);
            }

            DB::commit();

            $refund->load(['order', 'items', 'store', 'cashier']);

            return $this->created($refund, '退貨單建立成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('退貨單建立失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得單一退貨單
     *
     * @param  Refund  $refund  退貨單
     */
    public function show(Refund $refund): JsonResponse
    {
        $refund->load(['order', 'order.customer', 'items', 'store', 'cashier', 'approver']);

        return $this->success($refund, '取得退貨單詳情成功');
    }

    /**
     * 更新退貨單
     *
     * @param  UpdateRefundRequest  $request  更新請求
     * @param  Refund  $refund  退貨單
     */
    public function update(UpdateRefundRequest $request, Refund $refund): JsonResponse
    {
        // 只允許更新已完成狀態的退貨單的備註
        if ($refund->status !== 'COMPLETED') {
            return $this->error('只能更新已完成狀態的退貨單', 422);
        }

        $refund->update($request->validated());
        $refund->load(['order', 'items', 'cashier', 'approver']);

        return $this->success($refund, '退貨單更新成功');
    }

    /**
     * 刪除退貨單
     *
     * @param  Refund  $refund  退貨單
     */
    public function destroy(Refund $refund): JsonResponse
    {
        // 只允許刪除已取消的退貨單
        if ($refund->status !== 'CANCELLED') {
            return $this->error('只能刪除已取消的退貨單', 422);
        }

        try {
            DB::beginTransaction();

            $refund->items()->delete();
            $refund->delete();

            DB::commit();

            return $this->success(null, '退貨單刪除成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('退貨單刪除失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得退貨明細
     *
     * @param  Refund  $refund  退貨單
     */
    public function items(Refund $refund): JsonResponse
    {
        $items = $refund->items;

        return $this->success($items, '取得退貨明細成功');
    }

    /**
     * 取消退貨單
     *
     * @param  Refund  $refund  退貨單
     */
    public function cancel(Refund $refund): JsonResponse
    {
        if ($refund->status !== 'COMPLETED') {
            return $this->error('只能取消已完成狀態的退貨單', 422);
        }

        try {
            DB::beginTransaction();

            $order = $refund->order;

            // 如果有扣除點數，要退還點數
            if ($refund->points_deducted > 0 && $order->customer_id) {
                $customer = Customer::find($order->customer_id);
                if ($customer) {
                    $customer->available_points += $refund->points_deducted;
                    $customer->save();

                    PointsLog::create([
                        'customer_id' => $customer->id,
                        'type' => 'ADJUST',
                        'points' => $refund->points_deducted,
                        'balance' => $customer->available_points,
                        'reference_type' => 'REFUND',
                        'reference_id' => $refund->id,
                        'description' => "退貨單 {$refund->refund_no} 取消退還點數",
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            $refund->status = 'CANCELLED';
            $refund->save();

            // 如果訂單狀態是REFUNDED，恢復為COMPLETED
            if ($order->status === 'REFUNDED') {
                $order->status = 'COMPLETED';
                $order->save();
            }

            DB::commit();

            $refund->load(['order', 'items', 'cashier', 'approver']);

            return $this->success($refund, '退貨單已取消');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('退貨單取消失敗：'.$e->getMessage());
        }
    }
}
