<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierPrice\StoreSupplierPriceRequest;
use App\Http\Requests\SupplierPrice\UpdateSupplierPriceRequest;
use App\Models\SupplierPrice;
use App\Models\SupplierPriceHistory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 供應商報價控制器
 *
 * 處理供應商報價的 CRUD 操作及報價歷史查詢
 */
class SupplierPriceController extends Controller
{
    use ApiResponse;

    /**
     * 取得報價列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = SupplierPrice::with(['supplier', 'product']);

        // 供應商篩選
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // 商品篩選
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // 主要報價篩選
        if ($request->filled('is_primary')) {
            $query->where('is_primary', $request->boolean('is_primary'));
        }

        // 有效報價篩選
        if ($request->filled('is_valid')) {
            $isValid = $request->boolean('is_valid');
            if ($isValid) {
                $now = now()->toDateString();
                $query->where('is_active', true)
                    ->where('effective_from', '<=', $now)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('effective_to')
                            ->orWhere('effective_to', '>=', $now);
                    });
            }
        }

        // 關鍵字搜尋
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->whereHas('product', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('sku', 'like', "%{$keyword}%");
            });
        }

        // 排序
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分頁
        $perPage = $request->input('per_page', 15);
        $prices = $query->paginate($perPage);

        return $this->paginated($prices, '查詢報價列表成功');
    }

    /**
     * 新增報價
     *
     * @param  StoreSupplierPriceRequest  $request  新增請求
     */
    public function store(StoreSupplierPriceRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            $price = SupplierPrice::create($data);

            // 記錄報價歷史
            SupplierPriceHistory::create([
                'supplier_price_id' => $price->id,
                'supplier_id' => $price->supplier_id,
                'product_id' => $price->product_id,
                'variant_id' => $price->variant_id,
                'old_price' => 0,
                'new_price' => $price->unit_price,
                'price_change' => $price->unit_price,
                'change_percentage' => 0,
                'change_reason' => '新增報價',
                'effective_date' => $price->effective_from,
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            $price->load(['supplier', 'product']);

            return $this->created($price, '報價建立成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('報價建立失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得單一報價
     *
     * @param  SupplierPrice  $supplierPrice  報價
     */
    public function show(SupplierPrice $supplierPrice): JsonResponse
    {
        $supplierPrice->load(['supplier', 'product', 'histories']);

        return $this->success($supplierPrice, '取得報價詳情成功');
    }

    /**
     * 更新報價
     *
     * @param  UpdateSupplierPriceRequest  $request  更新請求
     * @param  SupplierPrice  $supplierPrice  報價
     */
    public function update(UpdateSupplierPriceRequest $request, SupplierPrice $supplierPrice): JsonResponse
    {
        try {
            DB::beginTransaction();

            $oldPrice = $supplierPrice->unit_price;
            $data = $request->validated();

            $supplierPrice->update($data);

            // 如果價格有變更，記錄報價歷史
            if (isset($data['unit_price']) && $data['unit_price'] != $oldPrice) {
                $newPrice = $data['unit_price'];
                $priceChange = $newPrice - $oldPrice;
                $changePercentage = $oldPrice > 0 ? ($priceChange / $oldPrice) * 100 : 0;

                SupplierPriceHistory::create([
                    'supplier_price_id' => $supplierPrice->id,
                    'supplier_id' => $supplierPrice->supplier_id,
                    'product_id' => $supplierPrice->product_id,
                    'variant_id' => $supplierPrice->variant_id,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'price_change' => $priceChange,
                    'change_percentage' => $changePercentage,
                    'change_reason' => '更新報價',
                    'effective_date' => $data['effective_from'] ?? $supplierPrice->effective_from,
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();

            $supplierPrice->load(['supplier', 'product']);

            return $this->success($supplierPrice, '報價更新成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('報價更新失敗：'.$e->getMessage());
        }
    }

    /**
     * 刪除報價
     *
     * @param  SupplierPrice  $supplierPrice  報價
     */
    public function destroy(SupplierPrice $supplierPrice): JsonResponse
    {
        try {
            DB::beginTransaction();

            // 刪除報價歷史
            $supplierPrice->histories()->delete();
            $supplierPrice->delete();

            DB::commit();

            return $this->success(null, '報價刪除成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('報價刪除失敗：'.$e->getMessage());
        }
    }

    /**
     * 查詢報價歷史
     *
     * @param  SupplierPrice  $supplierPrice  報價
     */
    public function history(SupplierPrice $supplierPrice): JsonResponse
    {
        $histories = $supplierPrice->histories()
            ->orderBy('effective_date', 'desc')
            ->get();

        return $this->success($histories, '查詢報價歷史成功');
    }
}
