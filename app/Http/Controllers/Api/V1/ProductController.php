<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 商品控制器
 *
 * 處理商品主檔的 CRUD 操作
 */
class ProductController extends Controller
{
    use ApiResponse;

    /**
     * 取得商品列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'variants', 'barcodes']);

        // 搜尋條件
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('sku', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('barcode', 'like', "%{$keyword}%");
            });
        }

        // 分類篩選
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // 品牌篩選
        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 價格範圍
        if ($request->filled('min_price')) {
            $query->where('selling_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('selling_price', '<=', $request->max_price);
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $products = $query->paginate($perPage);

        return $this->paginated($products, '查詢商品列表成功');
    }

    /**
     * 新增商品
     *
     * @param  StoreProductRequest  $request  新增請求
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['created_by'] = auth()->id();

            $product = Product::create($data);

            // 如果有條碼，自動建立主要條碼
            if (! empty($data['barcode'])) {
                $product->barcodes()->create([
                    'barcode' => $data['barcode'],
                    'barcode_type' => 'INTERNAL',
                    'is_primary' => true,
                ]);
            }

            DB::commit();

            $product->load(['category', 'variants', 'barcodes']);

            return $this->created($product, '商品建立成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('商品建立失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得單一商品
     *
     * @param  Product  $product  商品
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'variants', 'barcodes']);

        return $this->success($product, '取得商品詳情成功');
    }

    /**
     * 更新商品
     *
     * @param  UpdateProductRequest  $request  更新請求
     * @param  Product  $product  商品
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());
        $product->load(['category', 'variants', 'barcodes']);

        return $this->success($product, '商品更新成功');
    }

    /**
     * 刪除商品
     *
     * @param  Product  $product  商品
     */
    public function destroy(Product $product): JsonResponse
    {
        // 檢查是否有庫存
        if ($product->inventory()->where('quantity', '>', 0)->exists()) {
            return $this->error('此商品有庫存，無法刪除', 422);
        }

        // 檢查是否有訂單
        if ($product->orderItems()->exists()) {
            return $this->error('此商品有關聯的訂單，無法刪除', 422);
        }

        try {
            DB::beginTransaction();

            // 刪除關聯資料
            $product->barcodes()->delete();
            $product->variants()->delete();
            $product->delete();

            DB::commit();

            return $this->success(null, '商品刪除成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('商品刪除失敗：'.$e->getMessage());
        }
    }
}
