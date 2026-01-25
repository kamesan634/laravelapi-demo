<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 供應商控制器
 *
 * 處理供應商資料的 CRUD 操作
 */
class SupplierController extends Controller
{
    use ApiResponse;

    /**
     * 取得供應商列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query();

        // 搜尋條件
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('contact_person', 'like', "%{$keyword}%");
            });
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $suppliers = $query->paginate($perPage);

        return $this->paginated($suppliers, '查詢供應商列表成功');
    }

    /**
     * 新增供應商
     *
     * @param  StoreSupplierRequest  $request  新增請求
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::create($request->validated());

        return $this->created($supplier, '供應商建立成功');
    }

    /**
     * 取得單一供應商
     *
     * @param  Supplier  $supplier  供應商
     */
    public function show(Supplier $supplier): JsonResponse
    {
        return $this->success($supplier, '取得供應商詳情成功');
    }

    /**
     * 更新供應商
     *
     * @param  UpdateSupplierRequest  $request  更新請求
     * @param  Supplier  $supplier  供應商
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $supplier->update($request->validated());

        return $this->success($supplier, '供應商更新成功');
    }

    /**
     * 刪除供應商
     *
     * @param  Supplier  $supplier  供應商
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        // 檢查是否有採購單
        if ($supplier->purchaseOrders()->exists()) {
            return $this->error('此供應商有關聯的採購單，無法刪除', 422);
        }

        $supplier->delete();

        return $this->success(null, '供應商刪除成功');
    }
}
