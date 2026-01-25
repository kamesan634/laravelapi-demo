<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductVariant\StoreProductVariantRequest;
use App\Http\Requests\ProductVariant\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * 商品規格控制器
 *
 * 處理商品規格（SKU）的 CRUD 操作
 */
class ProductVariantController extends Controller
{
    use ApiResponse;

    /**
     * 新增商品規格
     *
     * @param  StoreProductVariantRequest  $request  新增請求
     * @param  Product  $product  商品
     */
    public function store(StoreProductVariantRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $data['product_id'] = $product->id;

        $variant = ProductVariant::create($data);

        return $this->created($variant, '商品規格建立成功');
    }

    /**
     * 更新商品規格
     *
     * @param  UpdateProductVariantRequest  $request  更新請求
     * @param  Product  $product  商品
     * @param  ProductVariant  $variant  規格
     */
    public function update(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant): JsonResponse
    {
        // 驗證規格屬於此商品
        if ($variant->product_id !== $product->id) {
            return $this->notFound('規格不屬於此商品');
        }

        $variant->update($request->validated());

        return $this->success($variant, '商品規格更新成功');
    }

    /**
     * 刪除商品規格
     *
     * @param  Product  $product  商品
     * @param  ProductVariant  $variant  規格
     */
    public function destroy(Product $product, ProductVariant $variant): JsonResponse
    {
        // 驗證規格屬於此商品
        if ($variant->product_id !== $product->id) {
            return $this->notFound('規格不屬於此商品');
        }

        // 檢查是否有庫存（數量大於零）
        if ($variant->inventory()->where('quantity', '>', 0)->exists()) {
            return $this->error('此規格有庫存，無法刪除', 422);
        }

        // 檢查是否有訂單
        if ($variant->orderItems()->exists()) {
            return $this->error('此規格有關聯的訂單，無法刪除', 422);
        }

        // 刪除零庫存的庫存記錄
        $variant->inventory()->where('quantity', '<=', 0)->delete();

        $variant->delete();

        return $this->success(null, '商品規格刪除成功');
    }
}
