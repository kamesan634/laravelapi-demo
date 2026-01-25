<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 會員控制器
 *
 * 處理會員資料的 CRUD 操作
 */
class CustomerController extends Controller
{
    use ApiResponse;

    /**
     * 取得會員列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::with(['level', 'joinStore']);

        // 搜尋條件
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('member_no', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        // 等級篩選
        if ($request->filled('level_id')) {
            $query->where('level_id', $request->level_id);
        }

        // 加入門市篩選
        if ($request->filled('join_store_id')) {
            $query->where('join_store_id', $request->join_store_id);
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
        $customers = $query->paginate($perPage);

        return $this->paginated($customers, '查詢會員列表成功');
    }

    /**
     * 新增會員
     *
     * @param  StoreCustomerRequest  $request  新增請求
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $data = $request->validated();

        // 如果沒有指定等級，使用等級代碼最低的（即最基礎的等級）
        if (empty($data['level_id'])) {
            $defaultLevel = CustomerLevel::orderBy('level_code', 'asc')->first();
            if ($defaultLevel) {
                $data['level_id'] = $defaultLevel->id;
            }
        }

        // 設定初始值
        $data['total_points'] = 0;
        $data['available_points'] = 0;
        $data['total_spending'] = 0;

        $customer = Customer::create($data);
        $customer->load(['level', 'joinStore']);

        return $this->created($customer, '會員建立成功');
    }

    /**
     * 取得單一會員
     *
     * @param  Customer  $customer  會員
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['level', 'joinStore']);

        return $this->success($customer, '取得會員詳情成功');
    }

    /**
     * 更新會員
     *
     * @param  UpdateCustomerRequest  $request  更新請求
     * @param  Customer  $customer  會員
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer->update($request->validated());
        $customer->load(['level', 'joinStore']);

        return $this->success($customer, '會員更新成功');
    }

    /**
     * 刪除會員
     *
     * @param  Customer  $customer  會員
     */
    public function destroy(Customer $customer): JsonResponse
    {
        // 檢查是否有訂單
        if ($customer->orders()->exists()) {
            return $this->error('此會員有關聯的訂單，無法刪除', 422);
        }

        $customer->delete();

        return $this->success(null, '會員刪除成功');
    }
}
