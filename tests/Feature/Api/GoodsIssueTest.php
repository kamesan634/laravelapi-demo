<?php

/**
 * 出貨單 API 測試
 *
 * 測試出貨單的 CRUD 操作及確認出庫流程
 */

use App\Models\Category;
use App\Models\GoodsIssue;
use App\Models\GoodsIssueItem;
use App\Models\Inventory;
use App\Models\Product;
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

    // 建立預設庫存
    $this->inventory = Inventory::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
    ]);
});

// ==================== 列表測試 ====================

// 測試取得出貨單列表
it('已認證使用者可以取得出貨單列表', function () {
    $user = actingAsAuthenticatedUser();

    GoodsIssue::create([
        'issue_no' => 'GI202401010001',
        'warehouse_id' => $this->warehouse->id,
        'issue_date' => now(),
        'issue_type' => 'SALES',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl('goods-issues'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
});

// 測試未認證取得出貨單列表
it('未認證使用者取得出貨單列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('goods-issues'));

    $response->assertStatus(401);
});

// ==================== 新增測試 ====================

// 測試成功新增出貨單 - 銷售出庫
it('已認證使用者可以新增銷售出庫單', function () {
    actingAsAuthenticatedUser();

    $issueData = [
        'warehouse_id' => $this->warehouse->id,
        'issue_type' => 'SALES',
        'issue_date' => now()->toDateString(),
        'remark' => '銷售出貨',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
                'unit_cost' => 50,
            ],
        ],
    ];

    $response = $this->postJson(apiUrl('goods-issues'), $issueData);

    $response->assertStatus(201);
});

// 測試成功新增出貨單 - 退貨出庫
it('已認證使用者可以新增退貨出庫單', function () {
    actingAsAuthenticatedUser();

    $issueData = [
        'warehouse_id' => $this->warehouse->id,
        'issue_type' => 'RETURN',
        'issue_date' => now()->toDateString(),
        'remark' => '退回供應商',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
            ],
        ],
    ];

    $response = $this->postJson(apiUrl('goods-issues'), $issueData);

    $response->assertStatus(201);
});

// 測試成功新增出貨單 - 調撥出庫
it('已認證使用者可以新增調撥出庫單', function () {
    actingAsAuthenticatedUser();

    $issueData = [
        'warehouse_id' => $this->warehouse->id,
        'issue_type' => 'TRANSFER',
        'issue_date' => now()->toDateString(),
        'remark' => '調撥至其他倉庫',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 20,
            ],
        ],
    ];

    $response = $this->postJson(apiUrl('goods-issues'), $issueData);

    $response->assertStatus(201);
});

// 測試成功新增出貨單 - 調整出庫
it('已認證使用者可以新增調整出庫單', function () {
    actingAsAuthenticatedUser();

    $issueData = [
        'warehouse_id' => $this->warehouse->id,
        'issue_type' => 'ADJUST',
        'issue_date' => now()->toDateString(),
        'remark' => '庫存調整出庫',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 3,
            ],
        ],
    ];

    $response = $this->postJson(apiUrl('goods-issues'), $issueData);

    $response->assertStatus(201);
});

// 測試成功新增出貨單 - 報廢出庫
it('已認證使用者可以新增報廢出庫單', function () {
    actingAsAuthenticatedUser();

    $issueData = [
        'warehouse_id' => $this->warehouse->id,
        'issue_type' => 'SCRAP',
        'issue_date' => now()->toDateString(),
        'remark' => '過期商品報廢',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 5,
            ],
        ],
    ];

    $response = $this->postJson(apiUrl('goods-issues'), $issueData);

    $response->assertStatus(201);
});

// 測試成功新增出貨單 - 其他出庫
it('已認證使用者可以新增其他出庫單', function () {
    actingAsAuthenticatedUser();

    $issueData = [
        'warehouse_id' => $this->warehouse->id,
        'issue_type' => 'OTHER',
        'issue_date' => now()->toDateString(),
        'remark' => '其他原因出庫',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 2,
            ],
        ],
    ];

    $response = $this->postJson(apiUrl('goods-issues'), $issueData);

    $response->assertStatus(201);
});

// 測試新增出貨單驗證錯誤 - 缺少必填欄位
it('新增出貨單時缺少必填欄位會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('goods-issues'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['warehouse_id', 'issue_type', 'issue_date', 'items']);
});

// 測試新增出貨單驗證錯誤 - 倉庫不存在
it('新增出貨單時倉庫不存在會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('goods-issues'), [
        'warehouse_id' => 99999,
        'issue_type' => 'SALES',
        'issue_date' => now()->toDateString(),
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['warehouse_id']);
});

// 測試新增出貨單驗證錯誤 - 出貨類型無效
it('新增出貨單時出貨類型無效會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('goods-issues'), [
        'warehouse_id' => $this->warehouse->id,
        'issue_type' => 'INVALID_TYPE',
        'issue_date' => now()->toDateString(),
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['issue_type']);
});

// 測試新增出貨單驗證錯誤 - 明細為空
it('新增出貨單時明細為空會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('goods-issues'), [
        'warehouse_id' => $this->warehouse->id,
        'issue_type' => 'SALES',
        'issue_date' => now()->toDateString(),
        'items' => [],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

// 測試新增出貨單驗證錯誤 - 商品不存在
it('新增出貨單時商品不存在會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('goods-issues'), [
        'warehouse_id' => $this->warehouse->id,
        'issue_type' => 'SALES',
        'issue_date' => now()->toDateString(),
        'items' => [
            ['product_id' => 99999, 'quantity' => 10],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.product_id']);
});

// 測試新增出貨單驗證錯誤 - 數量無效
it('新增出貨單時數量小於等於零會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('goods-issues'), [
        'warehouse_id' => $this->warehouse->id,
        'issue_type' => 'SALES',
        'issue_date' => now()->toDateString(),
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 0],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.quantity']);
});

// 測試未認證新增出貨單
it('未認證使用者新增出貨單會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl('goods-issues'), [
        'warehouse_id' => $this->warehouse->id,
        'issue_type' => 'SALES',
        'issue_date' => now()->toDateString(),
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ]);

    $response->assertStatus(401);
});

// ==================== 查看單一出貨單測試 ====================

// 測試取得單一出貨單
it('已認證使用者可以取得單一出貨單詳情', function () {
    $user = actingAsAuthenticatedUser();

    $issue = GoodsIssue::create([
        'issue_no' => 'GI202401010001',
        'warehouse_id' => $this->warehouse->id,
        'issue_date' => now(),
        'issue_type' => 'SALES',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("goods-issues/{$issue->id}"));

    $response->assertStatus(200);
});

// 測試取得不存在的出貨單
it('取得不存在的出貨單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('goods-issues/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一出貨單
it('未認證使用者取得單一出貨單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $issue = GoodsIssue::create([
        'issue_no' => 'GI202401010001',
        'warehouse_id' => $this->warehouse->id,
        'issue_date' => now(),
        'issue_type' => 'SALES',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("goods-issues/{$issue->id}"));

    $response->assertStatus(401);
});

// ==================== 更新測試 ====================

// 測試成功更新出貨單
it('已認證使用者可以更新出貨單', function () {
    $user = actingAsAuthenticatedUser();

    $issue = GoodsIssue::create([
        'issue_no' => 'GI202401010001',
        'warehouse_id' => $this->warehouse->id,
        'issue_date' => now(),
        'issue_type' => 'SALES',
        'status' => 'PENDING',
        'notes' => '舊備註',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("goods-issues/{$issue->id}"), [
        'remark' => '新備註說明',
        'issue_date' => now()->addDay()->toDateString(),
    ]);

    $response->assertStatus(200);
});

// 測試更新不存在的出貨單
it('更新不存在的出貨單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('goods-issues/99999'), [
        'remark' => '備註',
    ]);

    $response->assertStatus(404);
});

// 測試未認證更新出貨單
it('未認證使用者更新出貨單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $issue = GoodsIssue::create([
        'issue_no' => 'GI202401010001',
        'warehouse_id' => $this->warehouse->id,
        'issue_date' => now(),
        'issue_type' => 'SALES',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("goods-issues/{$issue->id}"), [
        'remark' => '備註',
    ]);

    $response->assertStatus(401);
});

// ==================== 刪除測試 ====================

// 測試成功刪除出貨單
it('已認證使用者可以刪除待處理的出貨單', function () {
    $user = actingAsAuthenticatedUser();

    $issue = GoodsIssue::create([
        'issue_no' => 'GI202401010001',
        'warehouse_id' => $this->warehouse->id,
        'issue_date' => now(),
        'issue_type' => 'SALES',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->deleteJson(apiUrl("goods-issues/{$issue->id}"));

    $response->assertStatus(200);
});

// 測試刪除不存在的出貨單
it('刪除不存在的出貨單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->deleteJson(apiUrl('goods-issues/99999'));

    $response->assertStatus(404);
});

// 測試未認證刪除出貨單
it('未認證使用者刪除出貨單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $issue = GoodsIssue::create([
        'issue_no' => 'GI202401010001',
        'warehouse_id' => $this->warehouse->id,
        'issue_date' => now(),
        'issue_type' => 'SALES',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->deleteJson(apiUrl("goods-issues/{$issue->id}"));

    $response->assertStatus(401);
});

// ==================== 出貨明細測試 ====================

// 測試取得出貨明細
it('已認證使用者可以取得出貨明細', function () {
    $user = actingAsAuthenticatedUser();

    $issue = GoodsIssue::create([
        'issue_no' => 'GI202401010001',
        'warehouse_id' => $this->warehouse->id,
        'issue_date' => now(),
        'issue_type' => 'SALES',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    GoodsIssueItem::create([
        'issue_id' => $issue->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'unit_cost' => 50,
    ]);

    $response = $this->getJson(apiUrl("goods-issues/{$issue->id}/items"));

    $response->assertStatus(200);
});

// 測試未認證取得出貨明細
it('未認證使用者取得出貨明細會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $issue = GoodsIssue::create([
        'issue_no' => 'GI202401010001',
        'warehouse_id' => $this->warehouse->id,
        'issue_date' => now(),
        'issue_type' => 'SALES',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("goods-issues/{$issue->id}/items"));

    $response->assertStatus(401);
});

// ==================== 確認出庫測試 ====================

// 測試確認出庫
it('已認證使用者可以確認出庫', function () {
    $user = actingAsAuthenticatedUser();

    $issue = GoodsIssue::create([
        'issue_no' => 'GI202401010001',
        'warehouse_id' => $this->warehouse->id,
        'issue_date' => now(),
        'issue_type' => 'SALES',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    GoodsIssueItem::create([
        'issue_id' => $issue->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'unit_cost' => 50,
    ]);

    $response = $this->putJson(apiUrl("goods-issues/{$issue->id}/confirm"));

    $response->assertStatus(200);
});

// 測試確認不存在的出貨單
it('確認不存在的出貨單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('goods-issues/99999/confirm'));

    $response->assertStatus(404);
});

// 測試未認證確認出庫
it('未認證使用者確認出庫會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $issue = GoodsIssue::create([
        'issue_no' => 'GI202401010001',
        'warehouse_id' => $this->warehouse->id,
        'issue_date' => now(),
        'issue_type' => 'SALES',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("goods-issues/{$issue->id}/confirm"));

    $response->assertStatus(401);
});

// ==================== 多筆明細測試 ====================

// 測試新增含多筆明細的出貨單
it('可以新增含多筆明細的出貨單', function () {
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

    // 為第二個商品建立庫存
    Inventory::create([
        'product_id' => $product2->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 50,
        'reserved_quantity' => 0,
    ]);

    $issueData = [
        'warehouse_id' => $this->warehouse->id,
        'issue_type' => 'SALES',
        'issue_date' => now()->toDateString(),
        'remark' => '大量出貨',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 10,
                'unit_cost' => 50,
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 5,
                'unit_cost' => 80,
            ],
        ],
    ];

    $response = $this->postJson(apiUrl('goods-issues'), $issueData);

    $response->assertStatus(201);
});
