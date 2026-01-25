<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CashierShift\StoreCashierShiftRequest;
use App\Models\CashierShift;
use App\Models\Order;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 收銀班別控制器
 *
 * 處理收銀班別的開班、關班操作
 */
class CashierShiftController extends Controller
{
    use ApiResponse;

    /**
     * 取得班別列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = CashierShift::with(['store', 'cashier', 'approver']);

        // 門市篩選
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // 收銀員篩選
        if ($request->filled('cashier_id')) {
            $query->where('cashier_id', $request->cashier_id);
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('shift_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('shift_date', '<=', $request->end_date);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $shifts = $query->paginate($perPage);

        return $this->paginated($shifts, '查詢班別列表成功');
    }

    /**
     * 開班
     *
     * @param  StoreCashierShiftRequest  $request  開班請求
     */
    public function store(StoreCashierShiftRequest $request): JsonResponse
    {
        $data = $request->validated();

        // 檢查此收銀員是否已有進行中的班別
        $existingShift = CashierShift::where('cashier_id', auth()->id())
            ->where('status', 'OPEN')
            ->first();

        if ($existingShift) {
            return $this->error('您已有進行中的班別，請先關班', 422);
        }

        $shift = CashierShift::create([
            'store_id' => $data['store_id'],
            'pos_id' => $data['register_no'],
            'cashier_id' => auth()->id(),
            'shift_date' => today(),
            'start_time' => now(),
            'opening_cash' => $data['opening_cash'],
            'expected_cash' => $data['opening_cash'],
            'total_sales' => 0,
            'total_refunds' => 0,
            'total_transactions' => 0,
            'status' => 'OPEN',
        ]);

        $shift->load(['store', 'cashier']);

        return $this->created($shift, '開班成功');
    }

    /**
     * 取得單一班別
     *
     * @param  CashierShift  $cashierShift  班別
     */
    public function show(CashierShift $cashierShift): JsonResponse
    {
        $cashierShift->load(['store', 'cashier', 'approver']);

        // 計算班別期間的交易統計
        if ($cashierShift->status === 'OPEN') {
            $stats = $this->calculateShiftStats($cashierShift);
            $cashierShift->setAttribute('current_stats', $stats);
        }

        return $this->success($cashierShift, '取得班別詳情成功');
    }

    /**
     * 關班
     *
     * @param  CashierShift  $cashierShift  班別
     */
    public function close(Request $request, CashierShift $cashierShift): JsonResponse
    {
        // 驗證是否為本人的班別
        if ($cashierShift->cashier_id !== auth()->id()) {
            return $this->error('您只能關閉自己的班別', 403);
        }

        // 驗證狀態
        if ($cashierShift->status !== 'OPEN') {
            return $this->error('此班別已關閉', 422);
        }

        $request->validate([
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'difference_note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            DB::beginTransaction();

            // 計算班別統計
            $stats = $this->calculateShiftStats($cashierShift);

            $actualCash = $request->input('actual_cash');
            $expectedCash = $cashierShift->opening_cash + $stats['cash_sales'] - $stats['cash_refunds'];
            $cashDifference = $actualCash - $expectedCash;

            $cashierShift->update([
                'end_time' => now(),
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'cash_difference' => $cashDifference,
                'difference_note' => $request->input('difference_note'),
                'total_sales' => $stats['total_sales'],
                'total_refunds' => $stats['total_refunds'],
                'total_transactions' => $stats['total_transactions'],
                'status' => 'CLOSED',
            ]);

            DB::commit();

            $cashierShift->load(['store', 'cashier']);

            return $this->success($cashierShift, '關班成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('關班失敗：'.$e->getMessage());
        }
    }

    /**
     * 計算班別統計
     */
    private function calculateShiftStats(CashierShift $shift): array
    {
        // 取得班別期間的訂單
        $ordersQuery = Order::where('store_id', $shift->store_id)
            ->where('cashier_id', $shift->cashier_id)
            ->where('created_at', '>=', $shift->start_time);

        if ($shift->end_time) {
            $ordersQuery->where('created_at', '<=', $shift->end_time);
        }

        $orders = $ordersQuery->get();

        $totalSales = $orders->where('status', '!=', 'CANCELLED')->sum('total_amount');
        $totalRefunds = $orders->where('status', 'REFUNDED')->sum('total_amount');
        $totalTransactions = $orders->count();

        // 計算現金交易
        $orderIds = $orders->pluck('id');
        $cashPayments = Payment::whereIn('order_id', $orderIds)
            ->where('payment_method', 'CASH')
            ->where('status', 'SUCCESS')
            ->sum('amount');

        return [
            'total_sales' => $totalSales,
            'total_refunds' => $totalRefunds,
            'total_transactions' => $totalTransactions,
            'cash_sales' => $cashPayments,
            'cash_refunds' => 0, // 簡化處理
        ];
    }
}
