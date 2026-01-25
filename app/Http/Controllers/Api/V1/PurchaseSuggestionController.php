<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PurchaseSuggestionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 採購建議控制器
 *
 * 提供採購建議相關的 API 端點
 */
class PurchaseSuggestionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PurchaseSuggestionService $suggestionService
    ) {}

    /**
     * 取得採購建議列表
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->suggestionService->getSuggestions($request->all());

            return $this->success($data, '取得採購建議成功');
        } catch (\Exception $e) {
            return $this->serverError('取得採購建議失敗：'.$e->getMessage());
        }
    }

    /**
     * 產生採購建議（智能分析）
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'analysis_days' => ['nullable', 'integer', 'min:7', 'max:365'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
        ]);

        try {
            $data = $this->suggestionService->generateSuggestions($request->all());

            return $this->success($data, '產生採購建議成功');
        } catch (\Exception $e) {
            return $this->serverError('產生採購建議失敗：'.$e->getMessage());
        }
    }

    /**
     * 建議轉採購單
     */
    public function toOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ], [
            'supplier_id.required' => '供應商為必填',
            'supplier_id.exists' => '供應商不存在',
            'warehouse_id.required' => '倉庫為必填',
            'warehouse_id.exists' => '倉庫不存在',
            'items.required' => '採購明細為必填',
            'items.min' => '採購單至少需包含 1 個商品',
            'items.*.product_id.required' => '商品 ID 為必填',
            'items.*.product_id.exists' => '商品不存在',
            'items.*.quantity.required' => '數量為必填',
            'items.*.quantity.min' => '數量至少為 1',
            'items.*.unit_price.required' => '單價為必填',
        ]);

        try {
            $purchaseOrder = $this->suggestionService->createPurchaseOrder($validated);

            return $this->created($purchaseOrder, '採購單建立成功');
        } catch (\Exception $e) {
            return $this->serverError('採購單建立失敗：'.$e->getMessage());
        }
    }
}
