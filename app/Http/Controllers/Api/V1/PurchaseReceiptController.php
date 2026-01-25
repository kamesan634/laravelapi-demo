<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseReceipt\StorePurchaseReceiptRequest;
use App\Http\Requests\PurchaseReceipt\UpdatePurchaseReceiptRequest;
use App\Models\Inventory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 採購收貨控制器
 *
 * 處理採購收貨的 CRUD 操作及確認收貨
 */
class PurchaseReceiptController extends Controller
{
    use ApiResponse;

    /**
     * 取得收貨單列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = PurchaseReceipt::with(['purchaseOrder', 'supplier', 'warehouse', 'creator', 'approver']);

        // 搜尋條件
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('receipt_no', 'like', "%{$keyword}%")
                    ->orWhereHas('purchaseOrder', function ($pq) use ($keyword) {
                        $pq->where('po_no', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('supplier', function ($sq) use ($keyword) {
                        $sq->where('name', 'like', "%{$keyword}%");
                    });
            });
        }

        // 供應商篩選
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // 倉庫篩選
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('receipt_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('receipt_date', '<=', $request->end_date);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $receipts = $query->paginate($perPage);

        return $this->paginated($receipts, '查詢收貨單列表成功');
    }

    /**
     * 新增收貨單
     *
     * @param  StorePurchaseReceiptRequest  $request  新增請求
     */
    public function store(StorePurchaseReceiptRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // 取得採購單
            $purchaseOrder = PurchaseOrder::findOrFail($data['purchase_order_id']);

            // 驗證採購單狀態
            if (! in_array($purchaseOrder->status, ['APPROVED', 'PARTIAL'])) {
                return $this->error('此採購單狀態無法進行收貨', 422);
            }

            // 生成收貨單編號
            $receiptNo = 'GR'.date('Ymd').str_pad(
                PurchaseReceipt::whereDate('created_at', today())->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            // 驗證並處理收貨明細
            $totalAmount = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                $poItem = PurchaseOrderItem::findOrFail($item['po_item_id']);

                // 驗證是否屬於此採購單
                if ($poItem->po_id !== $purchaseOrder->id) {
                    return $this->error('收貨項目不屬於此採購單', 422);
                }

                // 驗證收貨數量
                $remainingQty = $poItem->quantity - $poItem->received_quantity;
                if ($item['received_quantity'] > $remainingQty) {
                    return $this->error("商品收貨數量超過未收數量（可收：{$remainingQty}）", 422);
                }

                $rejectedQty = $item['rejected_quantity'] ?? 0;
                $acceptedQty = $item['accepted_quantity'] ?? ($item['received_quantity'] - $rejectedQty);
                $itemTotal = $poItem->unit_price * $acceptedQty;
                $totalAmount += $itemTotal;

                $itemsData[] = [
                    'po_item_id' => $poItem->id,
                    'product_id' => $poItem->product_id,
                    'variant_id' => $poItem->variant_id,
                    'expected_quantity' => $poItem->quantity - $poItem->received_quantity,
                    'received_quantity' => $item['received_quantity'],
                    'rejected_quantity' => $rejectedQty,
                    'unit_price' => $poItem->unit_price,
                    'line_total' => $itemTotal,
                    'quality_notes' => $item['remark'] ?? null,
                    '_accepted_quantity' => $acceptedQty,
                ];
            }

            // 建立收貨單
            $receipt = PurchaseReceipt::create([
                'receipt_no' => $receiptNo,
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'receipt_date' => $data['receipt_date'],
                'warehouse_id' => $data['warehouse_id'],
                'status' => 'PENDING',
                'total_amount' => $totalAmount,
                'notes' => $data['remark'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // 建立收貨明細
            foreach ($itemsData as $itemData) {
                $itemData['receipt_id'] = $receipt->id;
                unset($itemData['_accepted_quantity']); // 移除計算用的暫時欄位
                PurchaseReceiptItem::create($itemData);
            }

            DB::commit();

            $receipt->load(['purchaseOrder', 'supplier', 'warehouse', 'items.product', 'creator']);

            return $this->created($receipt, '收貨單建立成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('收貨單建立失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得單一收貨單
     *
     * @param  PurchaseReceipt  $purchaseReceipt  收貨單
     */
    public function show(PurchaseReceipt $purchaseReceipt): JsonResponse
    {
        $purchaseReceipt->load(['purchaseOrder', 'supplier', 'warehouse', 'items.product', 'items.variant', 'creator', 'approver']);

        return $this->success($purchaseReceipt, '取得收貨單詳情成功');
    }

    /**
     * 更新收貨單
     *
     * @param  UpdatePurchaseReceiptRequest  $request  更新請求
     * @param  PurchaseReceipt  $purchaseReceipt  收貨單
     */
    public function update(UpdatePurchaseReceiptRequest $request, PurchaseReceipt $purchaseReceipt): JsonResponse
    {
        // 只允許更新待確認狀態的收貨單
        if ($purchaseReceipt->status !== 'PENDING') {
            return $this->error('只能更新待確認狀態的收貨單', 422);
        }

        $purchaseReceipt->update($request->validated());
        $purchaseReceipt->load(['purchaseOrder', 'supplier', 'warehouse', 'items.product', 'creator']);

        return $this->success($purchaseReceipt, '收貨單更新成功');
    }

    /**
     * 刪除收貨單
     *
     * @param  PurchaseReceipt  $purchaseReceipt  收貨單
     */
    public function destroy(PurchaseReceipt $purchaseReceipt): JsonResponse
    {
        // 只允許刪除待確認狀態的收貨單
        if ($purchaseReceipt->status !== 'PENDING') {
            return $this->error('只能刪除待確認狀態的收貨單', 422);
        }

        try {
            DB::beginTransaction();

            $purchaseReceipt->items()->delete();
            $purchaseReceipt->delete();

            DB::commit();

            return $this->success(null, '收貨單刪除成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('收貨單刪除失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得收貨明細
     *
     * @param  PurchaseReceipt  $purchaseReceipt  收貨單
     */
    public function items(PurchaseReceipt $purchaseReceipt): JsonResponse
    {
        $items = $purchaseReceipt->items()->with(['product', 'variant'])->get();

        return $this->success($items, '取得收貨明細成功');
    }

    /**
     * 確認收貨
     *
     * @param  PurchaseReceipt  $purchaseReceipt  收貨單
     */
    public function confirm(PurchaseReceipt $purchaseReceipt): JsonResponse
    {
        if ($purchaseReceipt->status !== 'PENDING') {
            return $this->error('只能確認待確認狀態的收貨單', 422);
        }

        try {
            DB::beginTransaction();

            // 更新庫存
            foreach ($purchaseReceipt->items as $item) {
                // 計算接受數量 = 實收數量 - 退回數量
                $acceptedQuantity = $item->received_quantity - ($item->rejected_quantity ?? 0);

                if ($acceptedQuantity > 0) {
                    // 更新或建立庫存
                    $inventory = Inventory::firstOrNew([
                        'warehouse_id' => $purchaseReceipt->warehouse_id,
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                    ]);

                    $inventory->quantity = ($inventory->quantity ?? 0) + $acceptedQuantity;
                    $inventory->last_movement_date = now();
                    $inventory->save();

                    // 更新採購單明細的已收數量
                    $poItem = PurchaseOrderItem::find($item->po_item_id);
                    if ($poItem) {
                        $poItem->received_quantity += $acceptedQuantity;
                        $poItem->save();
                    }
                }
            }

            // 更新收貨單狀態
            $purchaseReceipt->status = 'COMPLETED';
            $purchaseReceipt->approved_by = auth()->id();
            $purchaseReceipt->approved_at = now();
            $purchaseReceipt->save();

            // 更新採購單狀態
            $purchaseOrder = $purchaseReceipt->purchaseOrder;
            $totalOrdered = $purchaseOrder->items()->sum('quantity');
            $totalReceived = $purchaseOrder->items()->sum('received_quantity');

            if ($totalReceived >= $totalOrdered) {
                $purchaseOrder->status = 'COMPLETED';
            } else {
                $purchaseOrder->status = 'PARTIAL';
            }
            $purchaseOrder->save();

            DB::commit();

            $purchaseReceipt->load(['purchaseOrder', 'items', 'approver']);

            return $this->success($purchaseReceipt, '收貨確認成功，庫存已更新');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('收貨確認失敗：'.$e->getMessage());
        }
    }
}
