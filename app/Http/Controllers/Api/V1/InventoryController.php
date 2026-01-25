<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 庫存控制器
 *
 * 處理庫存查詢操作
 */
class InventoryController extends Controller
{
    use ApiResponse;

    /**
     * 查詢庫存清單
     */
    public function index(Request $request): JsonResponse
    {
        $query = Inventory::with(['product', 'variant', 'warehouse']);

        // 搜尋條件（商品名稱、編號）
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->whereHas('product', function ($q) use ($keyword) {
                $q->where('sku', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('barcode', 'like', "%{$keyword}%");
            });
        }

        // 倉庫篩選
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // 商品篩選
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // 分類篩選
        if ($request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // 低庫存篩選
        if ($request->filled('low_stock') && $request->boolean('low_stock')) {
            $query->whereHas('product', function ($q) {
                $q->whereColumn('inventory.quantity', '<=', 'products.min_stock');
            });
        }

        // 零庫存篩選
        if ($request->filled('zero_stock') && $request->boolean('zero_stock')) {
            $query->where('quantity', '<=', 0);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $inventories = $query->paginate($perPage);

        return $this->paginated($inventories, '查詢庫存清單成功');
    }

    /**
     * 查詢商品庫存
     *
     * @param  Product  $product  商品
     */
    public function show(Product $product): JsonResponse
    {
        // 取得該商品所有倉庫的庫存
        $inventories = Inventory::with(['warehouse', 'variant'])
            ->where('product_id', $product->id)
            ->get();

        // 計算總庫存
        $totalQuantity = $inventories->sum('quantity');
        $totalReserved = $inventories->sum('reserved_quantity');
        $totalAvailable = $totalQuantity - $totalReserved;

        return $this->success([
            'product' => $product->load(['category', 'variants']),
            'total_quantity' => $totalQuantity,
            'total_reserved' => $totalReserved,
            'total_available' => $totalAvailable,
            'inventory_by_warehouse' => $inventories,
        ], '查詢商品庫存成功');
    }
}
