<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 發票控制器
 *
 * 處理發票的查詢與作廢操作
 */
class InvoiceController extends Controller
{
    use ApiResponse;

    /**
     * 取得發票列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['order', 'order.customer', 'order.store']);

        // 搜尋條件
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('invoice_no', 'like', "%{$keyword}%")
                    ->orWhere('buyer_tax_id', 'like', "%{$keyword}%")
                    ->orWhere('buyer_name', 'like', "%{$keyword}%")
                    ->orWhereHas('order', function ($oq) use ($keyword) {
                        $oq->where('order_no', 'like', "%{$keyword}%");
                    });
            });
        }

        // 發票類型篩選
        if ($request->filled('invoice_type')) {
            $query->where('invoice_type', $request->invoice_type);
        }

        // 作廢狀態篩選
        if ($request->filled('void_flag')) {
            $query->where('void_flag', $request->boolean('void_flag'));
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('invoice_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('invoice_date', '<=', $request->end_date);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $invoices = $query->paginate($perPage);

        return $this->paginated($invoices, '查詢發票列表成功');
    }

    /**
     * 取得單一發票
     *
     * @param  Invoice  $invoice  發票
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['order', 'order.customer', 'order.store', 'order.items']);

        return $this->success($invoice, '取得發票詳情成功');
    }

    /**
     * 作廢發票
     *
     * @param  Invoice  $invoice  發票
     */
    public function void(Request $request, Invoice $invoice): JsonResponse
    {
        // 檢查是否已作廢
        if ($invoice->void_flag) {
            return $this->error('此發票已作廢', 422);
        }

        $request->validate([
            'void_reason' => ['required', 'string', 'max:255'],
        ]);

        $invoice->update([
            'void_flag' => true,
            'void_date' => today(),
            'void_reason' => $request->input('void_reason'),
        ]);

        return $this->success($invoice, '發票作廢成功');
    }
}
