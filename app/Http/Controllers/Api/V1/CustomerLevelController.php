<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerLevel\StoreCustomerLevelRequest;
use App\Http\Requests\CustomerLevel\UpdateCustomerLevelRequest;
use App\Models\CustomerLevel;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 會員等級控制器
 *
 * 處理會員等級的 CRUD 操作
 */
class CustomerLevelController extends Controller
{
    use ApiResponse;

    /**
     * 取得會員等級列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = CustomerLevel::query();

        // 搜尋條件
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where('name', 'like', "%{$keyword}%");
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 排序（依等級代碼）
        $query->orderBy('level_code', 'asc');

        // 分頁
        $perPage = $request->input('per_page', 15);
        $levels = $query->paginate($perPage);

        return $this->paginated($levels, '查詢會員等級列表成功');
    }

    /**
     * 新增會員等級
     *
     * @param  StoreCustomerLevelRequest  $request  新增請求
     */
    public function store(StoreCustomerLevelRequest $request): JsonResponse
    {
        $level = CustomerLevel::create($request->validated());

        return $this->created($level, '會員等級建立成功');
    }

    /**
     * 取得單一會員等級
     *
     * @param  CustomerLevel  $customerLevel  會員等級
     */
    public function show(CustomerLevel $customerLevel): JsonResponse
    {
        return $this->success($customerLevel, '取得會員等級詳情成功');
    }

    /**
     * 更新會員等級
     *
     * @param  UpdateCustomerLevelRequest  $request  更新請求
     * @param  CustomerLevel  $customerLevel  會員等級
     */
    public function update(UpdateCustomerLevelRequest $request, CustomerLevel $customerLevel): JsonResponse
    {
        $customerLevel->update($request->validated());

        return $this->success($customerLevel, '會員等級更新成功');
    }

    /**
     * 刪除會員等級
     *
     * @param  CustomerLevel  $customerLevel  會員等級
     */
    public function destroy(CustomerLevel $customerLevel): JsonResponse
    {
        // 檢查是否有會員使用此等級
        if ($customerLevel->customers()->exists()) {
            return $this->error('此等級有關聯的會員，無法刪除', 422);
        }

        $customerLevel->delete();

        return $this->success(null, '會員等級刪除成功');
    }
}
