<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 庫存異動控制器
 *
 * 處理庫存異動紀錄查詢
 */
class InventoryMovementController extends Controller
{
    use ApiResponse;

    /**
     * 查詢商品庫存異動紀錄
     *
     * @param  Product  $product  商品
     */
    public function index(Request $request, Product $product): JsonResponse
    {
        $query = InventoryMovement::where('product_id', $product->id)
            ->with(['warehouse', 'variant']);

        // 倉庫篩選
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // 異動類型篩選
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // 排序（預設按時間降序）
        $query->orderBy('created_at', 'desc');

        $movements = $query->get();

        return $this->success($movements, '查詢庫存異動紀錄成功');
    }
}
