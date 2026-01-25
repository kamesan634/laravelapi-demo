<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Models\Role;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 角色管理控制器
 *
 * 處理角色的 CRUD 操作
 */
class RoleController extends Controller
{
    use ApiResponse;

    /**
     * 取得角色列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Role::with('permissions');

        // 關鍵字搜尋
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('display_name', 'like', "%{$keyword}%");
            });
        }

        // 啟用狀態篩選
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $roles = $query->paginate($request->input('per_page', 15));

        return $this->paginated($roles, '查詢角色列表成功');
    }

    /**
     * 新增角色
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $role = Role::create($request->only([
                'name',
                'display_name',
                'description',
                'is_active',
            ]));

            // 設定權限
            if ($request->has('permission_ids')) {
                $role->syncPermissions($request->permission_ids);
            }

            $role->load('permissions');

            DB::commit();

            return $this->created($role, '角色建立成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('角色建立失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得角色詳情
     */
    public function show(Role $role): JsonResponse
    {
        $role->load('permissions');

        return $this->success($role, '取得角色詳情成功');
    }

    /**
     * 更新角色
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        try {
            DB::beginTransaction();

            $role->update($request->only([
                'name',
                'display_name',
                'description',
                'is_active',
            ]));

            // 更新權限
            if ($request->has('permission_ids')) {
                $role->syncPermissions($request->permission_ids);
            }

            $role->load('permissions');

            DB::commit();

            return $this->success($role, '角色更新成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('角色更新失敗：'.$e->getMessage());
        }
    }

    /**
     * 刪除角色
     */
    public function destroy(Role $role): JsonResponse
    {
        // 檢查是否有使用者使用此角色
        if ($role->users()->exists()) {
            return $this->error('此角色有使用者使用中，無法刪除', 422);
        }

        try {
            $role->delete();

            return $this->success(null, '角色刪除成功');
        } catch (\Exception $e) {
            return $this->serverError('角色刪除失敗：'.$e->getMessage());
        }
    }

    /**
     * 設定角色權限
     */
    public function permissions(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        try {
            $role->syncPermissions($request->permission_ids);
            $role->load('permissions');

            return $this->success($role, '角色權限設定成功');
        } catch (\Exception $e) {
            return $this->serverError('角色權限設定失敗：'.$e->getMessage());
        }
    }
}
