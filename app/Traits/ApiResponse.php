<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * API 回應格式 Trait
 *
 * 提供統一的 API 回應格式
 */
trait ApiResponse
{
    /**
     * 成功回應
     *
     * @param  mixed  $data  回應資料
     * @param  string  $message  訊息
     * @param  int  $statusCode  HTTP 狀態碼
     */
    protected function success(mixed $data = null, string $message = '操作成功', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * 建立成功回應
     *
     * @param  mixed  $data  回應資料
     * @param  string  $message  訊息
     */
    protected function created(mixed $data = null, string $message = '建立成功'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * 無內容回應
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * 錯誤回應
     *
     * @param  string  $message  錯誤訊息
     * @param  int  $statusCode  HTTP 狀態碼
     * @param  mixed  $errors  詳細錯誤
     */
    protected function error(string $message = '操作失敗', int $statusCode = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * 未授權回應
     *
     * @param  string  $message  訊息
     */
    protected function unauthorized(string $message = '未授權'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * 禁止存取回應
     *
     * @param  string  $message  訊息
     */
    protected function forbidden(string $message = '禁止存取'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * 找不到資源回應
     *
     * @param  string  $message  訊息
     */
    protected function notFound(string $message = '找不到資源'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * 驗證錯誤回應
     *
     * @param  mixed  $errors  驗證錯誤
     * @param  string  $message  訊息
     */
    protected function validationError(mixed $errors, string $message = '驗證失敗'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * 伺服器錯誤回應
     *
     * @param  string  $message  訊息
     */
    protected function serverError(string $message = '伺服器錯誤'): JsonResponse
    {
        return $this->error($message, 500);
    }

    /**
     * 分頁回應
     *
     * @param  mixed  $paginator  分頁器
     * @param  string  $message  訊息
     */
    protected function paginated(mixed $paginator, string $message = '查詢成功'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }
}
