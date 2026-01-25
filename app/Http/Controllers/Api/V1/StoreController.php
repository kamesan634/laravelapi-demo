<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreStoreRequest;
use App\Http\Requests\Store\UpdateStoreRequest;
use App\Models\Store;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 門市控制器
 *
 * 處理門市資料的 CRUD 操作
 */
class StoreController extends Controller
{
    use ApiResponse;

    /**
     * 取得門市列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Store::query();

        // 搜尋條件
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
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
        $stores = $query->paginate($perPage);

        return $this->paginated($stores, '查詢門市列表成功');
    }

    /**
     * 新增門市
     *
     * @param  StoreStoreRequest  $request  新增門市請求
     */
    public function store(StoreStoreRequest $request): JsonResponse
    {
        $store = Store::create($request->validated());

        return $this->created($store, '門市建立成功');
    }

    /**
     * 取得單一門市
     *
     * @param  Store  $store  門市
     */
    public function show(Store $store): JsonResponse
    {
        // 載入關聯
        $store->load(['warehouses']);

        return $this->success($store, '取得門市詳情成功');
    }

    /**
     * 更新門市
     *
     * @param  UpdateStoreRequest  $request  更新門市請求
     * @param  Store  $store  門市
     */
    public function update(UpdateStoreRequest $request, Store $store): JsonResponse
    {
        $store->update($request->validated());

        return $this->success($store, '門市更新成功');
    }

    /**
     * 刪除門市
     *
     * @param  Store  $store  門市
     */
    public function destroy(Store $store): JsonResponse
    {
        // 檢查是否有關聯資料
        if ($store->warehouses()->exists()) {
            return $this->error('此門市有關聯的倉庫，無法刪除', 422);
        }

        if ($store->orders()->exists()) {
            return $this->error('此門市有關聯的訂單，無法刪除', 422);
        }

        $store->delete();

        return $this->success(null, '門市刪除成功');
    }
}
