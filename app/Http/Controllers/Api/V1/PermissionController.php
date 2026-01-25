<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 權限管理控制器
 *
 * 處理權限的查詢操作
 */
class PermissionController extends Controller
{
    use ApiResponse;

    /**
     * 取得權限列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Permission::query();

        // 關鍵字搜尋
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('display_name', 'like', "%{$keyword}%");
            });
        }

        // 模組篩選
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        // 排序
        $query->orderBy('module')->orderBy('name');

        $permissions = $query->get();

        // 依模組分組
        $grouped = $permissions->groupBy('module')->map(function ($items, $module) {
            return [
                'module' => $module,
                'permissions' => $items->values(),
            ];
        })->values();

        return $this->success([
            'permissions' => $permissions,
            'grouped' => $grouped,
            'modules' => $permissions->pluck('module')->unique()->values(),
        ], '查詢權限列表成功');
    }
}
