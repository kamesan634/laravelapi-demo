<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseRequest;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 倉庫控制器
 *
 * 處理倉庫資料的 CRUD 操作
 */
class WarehouseController extends Controller
{
    use ApiResponse;

    /**
     * 取得倉庫列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Warehouse::with(['store']);

        // 搜尋條件
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        // 門市篩選
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // 倉庫類型篩選
        if ($request->filled('type')) {
            $query->where('type', $request->type);
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
        $warehouses = $query->paginate($perPage);

        return $this->paginated($warehouses, '查詢倉庫列表成功');
    }

    /**
     * 新增倉庫
     *
     * @param  StoreWarehouseRequest  $request  新增倉庫請求
     */
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $warehouse = Warehouse::create($request->validated());
        $warehouse->load(['store']);

        return $this->created($warehouse, '倉庫建立成功');
    }

    /**
     * 取得單一倉庫
     *
     * @param  Warehouse  $warehouse  倉庫
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        $warehouse->load(['store']);

        return $this->success($warehouse, '取得倉庫詳情成功');
    }

    /**
     * 更新倉庫
     *
     * @param  UpdateWarehouseRequest  $request  更新倉庫請求
     * @param  Warehouse  $warehouse  倉庫
     */
    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        $warehouse->update($request->validated());
        $warehouse->load(['store']);

        return $this->success($warehouse, '倉庫更新成功');
    }

    /**
     * 刪除倉庫
     *
     * @param  Warehouse  $warehouse  倉庫
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        // 檢查是否有庫存
        if ($warehouse->inventory()->exists()) {
            return $this->error('此倉庫有庫存資料，無法刪除', 422);
        }

        $warehouse->delete();

        return $this->success(null, '倉庫刪除成功');
    }
}
