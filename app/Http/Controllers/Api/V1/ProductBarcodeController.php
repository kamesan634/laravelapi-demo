<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductBarcode\StoreProductBarcodeRequest;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * 商品條碼控制器
 *
 * 處理商品條碼（一品多碼）的操作
 */
class ProductBarcodeController extends Controller
{
    use ApiResponse;

    /**
     * 新增商品條碼
     *
     * @param  StoreProductBarcodeRequest  $request  新增請求
     * @param  Product  $product  商品
     */
    public function store(StoreProductBarcodeRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $data['product_id'] = $product->id;

        // 如果設為主要條碼，取消其他主要條碼
        if (! empty($data['is_primary']) && $data['is_primary']) {
            ProductBarcode::where('product_id', $product->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $barcode = ProductBarcode::create($data);

        return $this->created($barcode, '商品條碼建立成功');
    }

    /**
     * 刪除商品條碼
     *
     * @param  Product  $product  商品
     * @param  ProductBarcode  $barcode  條碼
     */
    public function destroy(Product $product, ProductBarcode $barcode): JsonResponse
    {
        // 驗證條碼屬於此商品
        if ($barcode->product_id !== $product->id) {
            return $this->notFound('條碼不屬於此商品');
        }

        // 如果是主要條碼，不允許刪除
        if ($barcode->is_primary) {
            return $this->error('無法刪除主要條碼', 422);
        }

        $barcode->delete();

        return $this->success(null, '商品條碼刪除成功');
    }
}
