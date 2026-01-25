<?php

/**
 * 進貨單 API 測試
 *
 * 測試進貨單的 CRUD 操作及確認入庫流程
 */

use App\Models\Category;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;

// ==================== 測試前置作業 ====================

beforeEach(function () {
    // 建立預設分類
    $this->category = Category::create([
        'code' => 'CAT001',
        'name' => '測試分類',
        'status' => 'ACTIVE',
    ]);

    // 建立預設倉庫
    $this->warehouse = Warehouse::create([
        'code' => 'W001',
        'name' => '主倉庫',
        'type' => 'WAREHOUSE',
        'status' => 'ACTIVE',
    ]);

    // 建立預設供應商
    $this->supplier = Supplier::create([
        'code' => 'SUP001',
        'name' => '測試供應商',
        'status' => 'ACTIVE',
    ]);

    // 建立預設商品
    $this->product = Product::create([
        'sku' => 'SKU001',
        'name' => '測試商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'cost_price' => 50,
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);
});

// ==================== 列表測試 ====================

// 測試取得進貨單列表
it('已認證使用者可以取得進貨單列表', function () {
    $user = actingAsAuthenticatedUser();

    GoodsReceipt::create([
        'receipt_no' => 'GR202401010001',
        'warehouse_id' => $this->warehouse->id,
        'receipt_date' => now(),
        'receipt_type' => 'PURCHASE',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl('goods-receipts'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
});

// 測試未認證取得進貨單列表
it('未認證使用者取得進貨單列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('goods-receipts'));

    $response->assertStatus(401);
});

// ==================== 新增測試 ====================

// 測試成功新增進貨單 - 採購入庫
it('已認證使用者可以新增採購入庫單', function () {
    actingAsAuthenticatedUser();

    $receiptData = [
        'warehouse_id' => $this->warehouse->id,
        'receipt_type' => 'PURCHASE',
        'receipt_date' => now()->toDateString(),
        'supplier_id' => $this->supplier->id,
        'remark' => '採購到貨',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 100,
                'unit_cost' => 50,
            ],
        ],
    ];

    $response = $this->postJson(apiUrl('goods-receipts'), $receiptData);

    $response->assertStatus(201);
});

// 測試成功新增進貨單 - 退貨入庫
it('已認證使用者可以新增退貨入庫單', function () {
    actingAsAuthenticatedUser();

    $receiptData = [
        'warehouse_id' => $this->warehouse->id,
        'receipt_type' => 'RETURN',
        'receipt_date' => now()->toDateString(),
        'remark' => '客戶退貨',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
                'unit_cost' => 50,
            ],
        ],
    ];

    $response = $this->postJson(apiUrl('goods-receipts'), $receiptData);

    $response->assertStatus(201);
});

// 測試成功新增進貨單 - 調撥入庫
it('已認證使用者可以新增調撥入庫單', function () {
    actingAsAuthenticatedUser();

    $receiptData = [
        'warehouse_id' => $this->warehouse->id,
        'receipt_type' => 'TRANSFER',
        'receipt_date' => now()->toDateString(),
        'remark' => '從其他倉庫調撥',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 20,
            ],
        ],
    ];

    $response = $this->postJson(apiUrl('goods-receipts'), $receiptData);

    $response->assertStatus(201);
});

// 測試成功新增進貨單 - 調整入庫
it('已認證使用者可以新增調整入庫單', function () {
    actingAsAuthenticatedUser();

    $receiptData = [
        'warehouse_id' => $this->warehouse->id,
        'receipt_type' => 'ADJUST',
        'receipt_date' => now()->toDateString(),
        'remark' => '庫存調整',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
            ],
        ],
    ];

    $response = $this->postJson(apiUrl('goods-receipts'), $receiptData);

    $response->assertStatus(201);
});

// 測試成功新增進貨單 - 其他入庫
it('已認證使用者可以新增其他入庫單', function () {
    actingAsAuthenticatedUser();

    $receiptData = [
        'warehouse_id' => $this->warehouse->id,
        'receipt_type' => 'OTHER',
        'receipt_date' => now()->toDateString(),
        'remark' => '其他原因入庫',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
            ],
        ],
    ];

    $response = $this->postJson(apiUrl('goods-receipts'), $receiptData);

    $response->assertStatus(201);
});

// 測試新增進貨單驗證錯誤 - 缺少必填欄位
it('新增進貨單時缺少必填欄位會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('goods-receipts'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['warehouse_id', 'receipt_type', 'receipt_date', 'items']);
});

// 測試新增進貨單驗證錯誤 - 倉庫不存在
it('新增進貨單時倉庫不存在會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('goods-receipts'), [
        'warehouse_id' => 99999,
        'receipt_type' => 'PURCHASE',
        'receipt_date' => now()->toDateString(),
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['warehouse_id']);
});

// 測試新增進貨單驗證錯誤 - 進貨類型無效
it('新增進貨單時進貨類型無效會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('goods-receipts'), [
        'warehouse_id' => $this->warehouse->id,
        'receipt_type' => 'INVALID_TYPE',
        'receipt_date' => now()->toDateString(),
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['receipt_type']);
});

// 測試新增進貨單驗證錯誤 - 明細為空
it('新增進貨單時明細為空會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('goods-receipts'), [
        'warehouse_id' => $this->warehouse->id,
        'receipt_type' => 'PURCHASE',
        'receipt_date' => now()->toDateString(),
        'items' => [],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

// 測試新增進貨單驗證錯誤 - 商品不存在
it('新增進貨單時商品不存在會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('goods-receipts'), [
        'warehouse_id' => $this->warehouse->id,
        'receipt_type' => 'PURCHASE',
        'receipt_date' => now()->toDateString(),
        'items' => [
            ['product_id' => 99999, 'quantity' => 10],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.product_id']);
});

// 測試新增進貨單驗證錯誤 - 數量無效
it('新增進貨單時數量小於等於零會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('goods-receipts'), [
        'warehouse_id' => $this->warehouse->id,
        'receipt_type' => 'PURCHASE',
        'receipt_date' => now()->toDateString(),
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 0],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.quantity']);
});

// 測試新增進貨單驗證錯誤 - 供應商不存在
it('新增進貨單時供應商不存在會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('goods-receipts'), [
        'warehouse_id' => $this->warehouse->id,
        'receipt_type' => 'PURCHASE',
        'receipt_date' => now()->toDateString(),
        'supplier_id' => 99999,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['supplier_id']);
});

// 測試未認證新增進貨單
it('未認證使用者新增進貨單會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl('goods-receipts'), [
        'warehouse_id' => $this->warehouse->id,
        'receipt_type' => 'PURCHASE',
        'receipt_date' => now()->toDateString(),
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ]);

    $response->assertStatus(401);
});

// ==================== 查看單一進貨單測試 ====================

// 測試取得單一進貨單
it('已認證使用者可以取得單一進貨單詳情', function () {
    $user = actingAsAuthenticatedUser();

    $receipt = GoodsReceipt::create([
        'receipt_no' => 'GR202401010001',
        'warehouse_id' => $this->warehouse->id,
        'receipt_date' => now(),
        'receipt_type' => 'PURCHASE',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("goods-receipts/{$receipt->id}"));

    $response->assertStatus(200);
});

// 測試取得不存在的進貨單
it('取得不存在的進貨單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('goods-receipts/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一進貨單
it('未認證使用者取得單一進貨單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $receipt = GoodsReceipt::create([
        'receipt_no' => 'GR202401010001',
        'warehouse_id' => $this->warehouse->id,
        'receipt_date' => now(),
        'receipt_type' => 'PURCHASE',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("goods-receipts/{$receipt->id}"));

    $response->assertStatus(401);
});

// ==================== 更新測試 ====================

// 測試成功更新進貨單
it('已認證使用者可以更新進貨單', function () {
    $user = actingAsAuthenticatedUser();

    $receipt = GoodsReceipt::create([
        'receipt_no' => 'GR202401010001',
        'warehouse_id' => $this->warehouse->id,
        'receipt_date' => now(),
        'receipt_type' => 'PURCHASE',
        'status' => 'PENDING',
        'notes' => '舊備註',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("goods-receipts/{$receipt->id}"), [
        'remark' => '新備註說明',
        'receipt_date' => now()->addDay()->toDateString(),
    ]);

    $response->assertStatus(200);
});

// 測試更新不存在的進貨單
it('更新不存在的進貨單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('goods-receipts/99999'), [
        'remark' => '備註',
    ]);

    $response->assertStatus(404);
});

// 測試未認證更新進貨單
it('未認證使用者更新進貨單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $receipt = GoodsReceipt::create([
        'receipt_no' => 'GR202401010001',
        'warehouse_id' => $this->warehouse->id,
        'receipt_date' => now(),
        'receipt_type' => 'PURCHASE',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("goods-receipts/{$receipt->id}"), [
        'remark' => '備註',
    ]);

    $response->assertStatus(401);
});

// ==================== 刪除測試 ====================

// 測試成功刪除進貨單
it('已認證使用者可以刪除待處理的進貨單', function () {
    $user = actingAsAuthenticatedUser();

    $receipt = GoodsReceipt::create([
        'receipt_no' => 'GR202401010001',
        'warehouse_id' => $this->warehouse->id,
        'receipt_date' => now(),
        'receipt_type' => 'PURCHASE',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->deleteJson(apiUrl("goods-receipts/{$receipt->id}"));

    $response->assertStatus(200);
});

// 測試刪除不存在的進貨單
it('刪除不存在的進貨單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->deleteJson(apiUrl('goods-receipts/99999'));

    $response->assertStatus(404);
});

// 測試未認證刪除進貨單
it('未認證使用者刪除進貨單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $receipt = GoodsReceipt::create([
        'receipt_no' => 'GR202401010001',
        'warehouse_id' => $this->warehouse->id,
        'receipt_date' => now(),
        'receipt_type' => 'PURCHASE',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->deleteJson(apiUrl("goods-receipts/{$receipt->id}"));

    $response->assertStatus(401);
});

// ==================== 進貨明細測試 ====================

// 測試取得進貨明細
it('已認證使用者可以取得進貨明細', function () {
    $user = actingAsAuthenticatedUser();

    $receipt = GoodsReceipt::create([
        'receipt_no' => 'GR202401010001',
        'warehouse_id' => $this->warehouse->id,
        'receipt_date' => now(),
        'receipt_type' => 'PURCHASE',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    GoodsReceiptItem::create([
        'receipt_id' => $receipt->id,
        'product_id' => $this->product->id,
        'expected_quantity' => 100,
        'received_quantity' => 100,
        'unit_cost' => 50,
    ]);

    $response = $this->getJson(apiUrl("goods-receipts/{$receipt->id}/items"));

    $response->assertStatus(200);
});

// 測試未認證取得進貨明細
it('未認證使用者取得進貨明細會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $receipt = GoodsReceipt::create([
        'receipt_no' => 'GR202401010001',
        'warehouse_id' => $this->warehouse->id,
        'receipt_date' => now(),
        'receipt_type' => 'PURCHASE',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("goods-receipts/{$receipt->id}/items"));

    $response->assertStatus(401);
});

// ==================== 確認入庫測試 ====================

// 測試確認入庫
it('已認證使用者可以確認入庫', function () {
    $user = actingAsAuthenticatedUser();

    $receipt = GoodsReceipt::create([
        'receipt_no' => 'GR202401010001',
        'warehouse_id' => $this->warehouse->id,
        'receipt_date' => now(),
        'receipt_type' => 'PURCHASE',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    GoodsReceiptItem::create([
        'receipt_id' => $receipt->id,
        'product_id' => $this->product->id,
        'expected_quantity' => 100,
        'received_quantity' => 100,
        'unit_cost' => 50,
    ]);

    $response = $this->putJson(apiUrl("goods-receipts/{$receipt->id}/confirm"));

    $response->assertStatus(200);
});

// 測試確認不存在的進貨單
it('確認不存在的進貨單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('goods-receipts/99999/confirm'));

    $response->assertStatus(404);
});

// 測試未認證確認入庫
it('未認證使用者確認入庫會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $receipt = GoodsReceipt::create([
        'receipt_no' => 'GR202401010001',
        'warehouse_id' => $this->warehouse->id,
        'receipt_date' => now(),
        'receipt_type' => 'PURCHASE',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("goods-receipts/{$receipt->id}/confirm"));

    $response->assertStatus(401);
});

// ==================== 多筆明細測試 ====================

// 測試新增含多筆明細的進貨單
it('可以新增含多筆明細的進貨單', function () {
    actingAsAuthenticatedUser();

    $product2 = Product::create([
        'sku' => 'SKU002',
        'name' => '商品B',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'cost_price' => 80,
        'selling_price' => 150,
        'status' => 'ACTIVE',
    ]);

    $receiptData = [
        'warehouse_id' => $this->warehouse->id,
        'receipt_type' => 'PURCHASE',
        'receipt_date' => now()->toDateString(),
        'supplier_id' => $this->supplier->id,
        'remark' => '大量採購',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 100,
                'unit_cost' => 50,
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 50,
                'unit_cost' => 80,
            ],
        ],
    ];

    $response = $this->postJson(apiUrl('goods-receipts'), $receiptData);

    $response->assertStatus(201);
});
