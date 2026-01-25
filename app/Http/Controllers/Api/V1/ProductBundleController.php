<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductBundle\StoreProductBundleRequest;
use App\Http\Requests\ProductBundle\UpdateProductBundleRequest;
use App\Models\ProductBundle;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 組合商品控制器
 *
 * 處理組合商品的 CRUD 操作
 */
class ProductBundleController extends Controller
{
    use ApiResponse;

    /**
     * 取得組合商品列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProductBundle::with(['items.product']);

        // 關鍵字搜尋
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('sku', 'like', "%{$keyword}%");
            });
        }

        // 啟用狀態篩選
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $bundles = $query->paginate($request->input('per_page', 15));

        return $this->paginated($bundles, '查詢組合商品列表成功');
    }

    /**
     * 新增組合商品
     */
    public function store(StoreProductBundleRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $bundle = ProductBundle::create($request->only([
                'name',
                'sku',
                'description',
                'price',
                'is_active',
            ]));

            // 新增組合商品明細
            foreach ($request->items as $item) {
                $bundle->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            $bundle->load('items.product');

            DB::commit();

            return $this->created($bundle, '組合商品建立成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('組合商品建立失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得組合商品詳情
     */
    public function show(ProductBundle $productBundle): JsonResponse
    {
        $productBundle->load('items.product');

        // 計算原價和折扣
        $data = $productBundle->toArray();
        $data['original_price'] = $productBundle->original_price;
        $data['discount_amount'] = $productBundle->discount_amount;

        return $this->success($data, '取得組合商品詳情成功');
    }

    /**
     * 更新組合商品
     */
    public function update(UpdateProductBundleRequest $request, ProductBundle $productBundle): JsonResponse
    {
        try {
            DB::beginTransaction();

            $productBundle->update($request->only([
                'name',
                'sku',
                'description',
                'price',
                'is_active',
            ]));

            // 如果有更新明細，重新建立
            if ($request->has('items')) {
                $productBundle->items()->delete();

                foreach ($request->items as $item) {
                    $productBundle->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }

            $productBundle->load('items.product');

            DB::commit();

            return $this->success($productBundle, '組合商品更新成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('組合商品更新失敗：'.$e->getMessage());
        }
    }

    /**
     * 刪除組合商品
     */
    public function destroy(ProductBundle $productBundle): JsonResponse
    {
        try {
            $productBundle->delete();

            return $this->success(null, '組合商品刪除成功');
        } catch (\Exception $e) {
            return $this->serverError('組合商品刪除失敗：'.$e->getMessage());
        }
    }
}
