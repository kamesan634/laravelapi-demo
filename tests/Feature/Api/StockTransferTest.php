<?php

/**
 * 調撥單 API 測試
 *
 * 測試倉庫間庫存調撥的 CRUD 操作及調撥流程
 */

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;

// ==================== 測試前置作業 ====================

beforeEach(function () {
    // 建立預設分類
    $this->category = Category::create([
        'code' => 'CAT001',
        'name' => '測試分類',
        'status' => 'ACTIVE',
    ]);

    // 建立來源倉庫
    $this->fromWarehouse = Warehouse::create([
        'code' => 'W001',
        'name' => '來源倉庫',
        'type' => 'WAREHOUSE',
        'status' => 'ACTIVE',
    ]);

    // 建立目的倉庫
    $this->toWarehouse = Warehouse::create([
        'code' => 'W002',
        'name' => '目的倉庫',
        'type' => 'WAREHOUSE',
        'status' => 'ACTIVE',
    ]);

    // 建立預設商品
    $this->product = Product::create([
        'sku' => 'SKU001',
        'name' => '測試商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    // 建立來源倉庫庫存
    $this->inventory = Inventory::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->fromWarehouse->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
    ]);
});

// ==================== 列表測試 ====================

// 測試取得調撥單列表
it('已認證使用者可以取得調撥單列表', function () {
    $user = actingAsAuthenticatedUser();

    $transfer = StockTransfer::create([
        'transfer_no' => 'TRF202401010001',
        'transfer_date' => now(),
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    StockTransferItem::create([
        'transfer_id' => $transfer->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
    ]);

    $response = $this->getJson(apiUrl('stock-transfers'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
});

// 測試未認證取得調撥單列表
it('未認證使用者取得調撥單列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('stock-transfers'));

    $response->assertStatus(401);
});

// ==================== 新增測試 ====================

// 測試成功新增調撥單
it('已認證使用者可以新增調撥單', function () {
    actingAsAuthenticatedUser();

    $transferData = [
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'transfer_date' => now()->toDateString(),
        'expected_date' => now()->addDays(3)->toDateString(),
        'remark' => '倉庫間調撥',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 20,
            ],
        ],
    ];

    $response = $this->postJson(apiUrl('stock-transfers'), $transferData);

    $response->assertStatus(201);
});

// 測試新增調撥單驗證錯誤 - 缺少必填欄位
it('新增調撥單時缺少必填欄位會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stock-transfers'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['from_warehouse_id', 'to_warehouse_id', 'transfer_date', 'items']);
});

// 測試新增調撥單驗證錯誤 - 來源倉庫不存在
it('新增調撥單時來源倉庫不存在會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stock-transfers'), [
        'from_warehouse_id' => 99999,
        'to_warehouse_id' => $this->toWarehouse->id,
        'transfer_date' => now()->toDateString(),
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['from_warehouse_id']);
});

// 測試新增調撥單驗證錯誤 - 目的倉庫不存在
it('新增調撥單時目的倉庫不存在會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stock-transfers'), [
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => 99999,
        'transfer_date' => now()->toDateString(),
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['to_warehouse_id']);
});

// 測試新增調撥單驗證錯誤 - 來源與目的倉庫相同
it('新增調撥單時來源與目的倉庫相同會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stock-transfers'), [
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->fromWarehouse->id,
        'transfer_date' => now()->toDateString(),
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['to_warehouse_id']);
});

// 測試新增調撥單驗證錯誤 - 明細為空
it('新增調撥單時明細為空會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stock-transfers'), [
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'transfer_date' => now()->toDateString(),
        'items' => [],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

// 測試新增調撥單驗證錯誤 - 商品不存在
it('新增調撥單時商品不存在會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stock-transfers'), [
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'transfer_date' => now()->toDateString(),
        'items' => [
            ['product_id' => 99999, 'quantity' => 10],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.product_id']);
});

// 測試新增調撥單驗證錯誤 - 數量無效
it('新增調撥單時數量小於等於零會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stock-transfers'), [
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'transfer_date' => now()->toDateString(),
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 0],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.quantity']);
});

// 測試未認證新增調撥單
it('未認證使用者新增調撥單會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl('stock-transfers'), [
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'transfer_date' => now()->toDateString(),
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 10],
        ],
    ]);

    $response->assertStatus(401);
});

// ==================== 查看單一調撥單測試 ====================

// 測試取得單一調撥單
it('已認證使用者可以取得單一調撥單詳情', function () {
    $user = actingAsAuthenticatedUser();

    $transfer = StockTransfer::create([
        'transfer_no' => 'TRF202401010001',
        'transfer_date' => now(),
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("stock-transfers/{$transfer->id}"));

    $response->assertStatus(200);
});

// 測試取得不存在的調撥單
it('取得不存在的調撥單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('stock-transfers/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一調撥單
it('未認證使用者取得單一調撥單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $transfer = StockTransfer::create([
        'transfer_no' => 'TRF202401010001',
        'transfer_date' => now(),
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("stock-transfers/{$transfer->id}"));

    $response->assertStatus(401);
});

// ==================== 更新測試 ====================

// 測試成功更新調撥單
it('已認證使用者可以更新調撥單', function () {
    $user = actingAsAuthenticatedUser();

    $transfer = StockTransfer::create([
        'transfer_no' => 'TRF202401010001',
        'transfer_date' => now(),
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'status' => 'PENDING',
        'notes' => '舊備註',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("stock-transfers/{$transfer->id}"), [
        'remark' => '新備註說明',
        'expected_date' => now()->addDays(5)->toDateString(),
    ]);

    $response->assertStatus(200);
});

// 測試更新不存在的調撥單
it('更新不存在的調撥單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('stock-transfers/99999'), [
        'remark' => '備註',
    ]);

    $response->assertStatus(404);
});

// 測試未認證更新調撥單
it('未認證使用者更新調撥單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $transfer = StockTransfer::create([
        'transfer_no' => 'TRF202401010001',
        'transfer_date' => now(),
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("stock-transfers/{$transfer->id}"), [
        'remark' => '備註',
    ]);

    $response->assertStatus(401);
});

// ==================== 刪除測試 ====================

// 測試成功刪除調撥單
it('已認證使用者可以刪除待審核的調撥單', function () {
    $user = actingAsAuthenticatedUser();

    $transfer = StockTransfer::create([
        'transfer_no' => 'TRF202401010001',
        'transfer_date' => now(),
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->deleteJson(apiUrl("stock-transfers/{$transfer->id}"));

    $response->assertStatus(200);
});

// 測試刪除不存在的調撥單
it('刪除不存在的調撥單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->deleteJson(apiUrl('stock-transfers/99999'));

    $response->assertStatus(404);
});

// 測試未認證刪除調撥單
it('未認證使用者刪除調撥單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $transfer = StockTransfer::create([
        'transfer_no' => 'TRF202401010001',
        'transfer_date' => now(),
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->deleteJson(apiUrl("stock-transfers/{$transfer->id}"));

    $response->assertStatus(401);
});

// ==================== 調撥明細測試 ====================

// 測試取得調撥明細
it('已認證使用者可以取得調撥明細', function () {
    $user = actingAsAuthenticatedUser();

    $transfer = StockTransfer::create([
        'transfer_no' => 'TRF202401010001',
        'transfer_date' => now(),
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    StockTransferItem::create([
        'transfer_id' => $transfer->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
    ]);

    $response = $this->getJson(apiUrl("stock-transfers/{$transfer->id}/items"));

    $response->assertStatus(200);
});

// 測試未認證取得調撥明細
it('未認證使用者取得調撥明細會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $transfer = StockTransfer::create([
        'transfer_no' => 'TRF202401010001',
        'transfer_date' => now(),
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("stock-transfers/{$transfer->id}/items"));

    $response->assertStatus(401);
});

// ==================== 審核通過測試 ====================

// 測試審核通過調撥單
it('已認證使用者可以審核通過調撥單', function () {
    $user = actingAsAuthenticatedUser();

    $transfer = StockTransfer::create([
        'transfer_no' => 'TRF202401010001',
        'transfer_date' => now(),
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    StockTransferItem::create([
        'transfer_id' => $transfer->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
    ]);

    $response = $this->putJson(apiUrl("stock-transfers/{$transfer->id}/approve"));

    $response->assertStatus(200);
});

// 測試未認證審核調撥單
it('未認證使用者審核調撥單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $transfer = StockTransfer::create([
        'transfer_no' => 'TRF202401010001',
        'transfer_date' => now(),
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("stock-transfers/{$transfer->id}/approve"));

    $response->assertStatus(401);
});

// ==================== 出貨測試 ====================

// 測試出貨操作
it('已認證使用者可以執行出貨操作', function () {
    $user = actingAsAuthenticatedUser();

    $transfer = StockTransfer::create([
        'transfer_no' => 'TRF202401010001',
        'transfer_date' => now(),
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'status' => 'APPROVED',
        'created_by' => $user->id,
        'approved_by' => $user->id,
        'approved_at' => now(),
    ]);

    StockTransferItem::create([
        'transfer_id' => $transfer->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
    ]);

    $response = $this->putJson(apiUrl("stock-transfers/{$transfer->id}/ship"));

    $response->assertStatus(200);
});

// 測試未認證出貨操作
it('未認證使用者執行出貨操作會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $transfer = StockTransfer::create([
        'transfer_no' => 'TRF202401010001',
        'transfer_date' => now(),
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'status' => 'APPROVED',
        'created_by' => $user->id,
        'approved_by' => $user->id,
        'approved_at' => now(),
    ]);

    $response = $this->putJson(apiUrl("stock-transfers/{$transfer->id}/ship"));

    $response->assertStatus(401);
});

// ==================== 收貨測試 ====================

// 測試收貨操作
it('已認證使用者可以執行收貨操作', function () {
    $user = actingAsAuthenticatedUser();

    $transfer = StockTransfer::create([
        'transfer_no' => 'TRF202401010001',
        'transfer_date' => now(),
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'status' => 'IN_TRANSIT',
        'created_by' => $user->id,
        'approved_by' => $user->id,
        'approved_at' => now(),
        'shipped_by' => $user->id,
        'shipped_at' => now(),
    ]);

    StockTransferItem::create([
        'transfer_id' => $transfer->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
    ]);

    $response = $this->putJson(apiUrl("stock-transfers/{$transfer->id}/receive"));

    $response->assertStatus(200);
});

// 測試未認證收貨操作
it('未認證使用者執行收貨操作會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $transfer = StockTransfer::create([
        'transfer_no' => 'TRF202401010001',
        'transfer_date' => now(),
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'status' => 'IN_TRANSIT',
        'created_by' => $user->id,
        'approved_by' => $user->id,
        'approved_at' => now(),
        'shipped_by' => $user->id,
        'shipped_at' => now(),
    ]);

    $response = $this->putJson(apiUrl("stock-transfers/{$transfer->id}/receive"));

    $response->assertStatus(401);
});
