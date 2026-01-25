<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 操作日誌控制器
 *
 * 處理操作日誌的查詢
 */
class AuditLogController extends Controller
{
    use ApiResponse;

    /**
     * 取得操作日誌列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user:id,name,email');

        // 使用者篩選
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // 操作類型篩選
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // 模型類型篩選
        if ($request->filled('model_type')) {
            $query->where('model_type', 'like', '%'.$request->model_type.'%');
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // 排序
        $query->orderBy('created_at', 'desc');

        $logs = $query->paginate($request->input('per_page', 20));

        return $this->paginated($logs, '查詢操作日誌成功');
    }

    /**
     * 取得日誌詳情
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        $auditLog->load('user:id,name,email');

        return $this->success($auditLog, '取得日誌詳情成功');
    }

    /**
     * 取得使用者的操作記錄
     */
    public function byUser(Request $request, int $userId): JsonResponse
    {
        $query = AuditLog::with('user:id,name,email')
            ->where('user_id', $userId);

        // 操作類型篩選
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $query->orderBy('created_at', 'desc');

        $logs = $query->paginate($request->input('per_page', 20));

        return $this->paginated($logs, '查詢使用者操作記錄成功');
    }

    /**
     * 取得模型的操作記錄
     */
    public function byModel(Request $request, string $model): JsonResponse
    {
        // 將簡化的模型名稱轉換為完整類名
        $modelClass = 'App\\Models\\'.ucfirst($model);

        $query = AuditLog::with('user:id,name,email')
            ->where('model_type', $modelClass);

        // 模型 ID 篩選
        if ($request->filled('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        // 操作類型篩選
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $query->orderBy('created_at', 'desc');

        $logs = $query->paginate($request->input('per_page', 20));

        return $this->paginated($logs, '查詢模型操作記錄成功');
    }
}
