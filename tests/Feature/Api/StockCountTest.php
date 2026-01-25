<?php

/**
 * 盤點單 API 測試
 *
 * 測試庫存盤點的 CRUD 操作及盤點流程
 */

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockCount;
use App\Models\StockCountItem;
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

// 測試取得盤點單列表
it('已認證使用者可以取得盤點單列表', function () {
    $user = actingAsAuthenticatedUser();

    StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl('stock-counts'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);
});

// 測試未認證取得盤點單列表
it('未認證使用者取得盤點單列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('stock-counts'));

    $response->assertStatus(401);
});

// ==================== 新增測試 ====================

// 測試成功新增盤點單 - 全盤
it('已認證使用者可以新增全盤盤點單', function () {
    actingAsAuthenticatedUser();

    $countData = [
        'warehouse_id' => $this->warehouse->id,
        'count_type' => 'FULL',
        'count_date' => now()->toDateString(),
        'remark' => '年度全盤',
    ];

    $response = $this->postJson(apiUrl('stock-counts'), $countData);

    $response->assertStatus(201);
});

// 測試成功新增盤點單 - 部分盤點
it('已認證使用者可以新增部分盤點單', function () {
    actingAsAuthenticatedUser();

    $countData = [
        'warehouse_id' => $this->warehouse->id,
        'count_type' => 'PARTIAL',
        'count_date' => now()->toDateString(),
        'category_id' => $this->category->id,
        'remark' => '分類盤點',
    ];

    $response = $this->postJson(apiUrl('stock-counts'), $countData);

    $response->assertStatus(201);
});

// 測試成功新增盤點單 - 循環盤點
it('已認證使用者可以新增循環盤點單', function () {
    actingAsAuthenticatedUser();

    $countData = [
        'warehouse_id' => $this->warehouse->id,
        'count_type' => 'CYCLE',
        'count_date' => now()->toDateString(),
        'remark' => '循環盤點',
    ];

    $response = $this->postJson(apiUrl('stock-counts'), $countData);

    $response->assertStatus(201);
});

// 測試成功新增盤點單 - 抽盤（部分盤點）
it('已認證使用者可以新增抽盤盤點單', function () {
    actingAsAuthenticatedUser();

    $countData = [
        'warehouse_id' => $this->warehouse->id,
        'count_type' => 'PARTIAL',
        'count_date' => now()->toDateString(),
        'remark' => '抽盤',
    ];

    $response = $this->postJson(apiUrl('stock-counts'), $countData);

    $response->assertStatus(201);
});

// 測試新增盤點單驗證錯誤 - 缺少必填欄位
it('新增盤點單時缺少必填欄位會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stock-counts'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['warehouse_id', 'count_type', 'count_date']);
});

// 測試新增盤點單驗證錯誤 - 倉庫不存在
it('新增盤點單時倉庫不存在會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stock-counts'), [
        'warehouse_id' => 99999,
        'count_type' => 'FULL',
        'count_date' => now()->toDateString(),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['warehouse_id']);
});

// 測試新增盤點單驗證錯誤 - 盤點類型無效
it('新增盤點單時盤點類型無效會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stock-counts'), [
        'warehouse_id' => $this->warehouse->id,
        'count_type' => 'INVALID_TYPE',
        'count_date' => now()->toDateString(),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['count_type']);
});

// 測試新增盤點單驗證錯誤 - 分類不存在
it('新增盤點單時分類不存在會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stock-counts'), [
        'warehouse_id' => $this->warehouse->id,
        'count_type' => 'PARTIAL',
        'count_date' => now()->toDateString(),
        'category_id' => 99999,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['category_id']);
});

// 測試未認證新增盤點單
it('未認證使用者新增盤點單會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl('stock-counts'), [
        'warehouse_id' => $this->warehouse->id,
        'count_type' => 'FULL',
        'count_date' => now()->toDateString(),
    ]);

    $response->assertStatus(401);
});

// ==================== 查看單一盤點單測試 ====================

// 測試取得單一盤點單
it('已認證使用者可以取得單一盤點單詳情', function () {
    $user = actingAsAuthenticatedUser();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("stock-counts/{$count->id}"));

    $response->assertStatus(200);
});

// 測試取得不存在的盤點單
it('取得不存在的盤點單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('stock-counts/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一盤點單
it('未認證使用者取得單一盤點單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("stock-counts/{$count->id}"));

    $response->assertStatus(401);
});

// ==================== 更新測試 ====================

// 測試成功更新盤點單
it('已認證使用者可以更新盤點單', function () {
    $user = actingAsAuthenticatedUser();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'notes' => '舊備註',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("stock-counts/{$count->id}"), [
        'remark' => '新備註說明',
        'count_date' => now()->addDay()->toDateString(),
    ]);

    $response->assertStatus(200);
});

// 測試更新不存在的盤點單
it('更新不存在的盤點單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('stock-counts/99999'), [
        'remark' => '備註',
    ]);

    $response->assertStatus(404);
});

// 測試未認證更新盤點單
it('未認證使用者更新盤點單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("stock-counts/{$count->id}"), [
        'remark' => '備註',
    ]);

    $response->assertStatus(401);
});

// ==================== 刪除測試 ====================

// 測試成功刪除盤點單
it('已認證使用者可以刪除待處理的盤點單', function () {
    $user = actingAsAuthenticatedUser();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->deleteJson(apiUrl("stock-counts/{$count->id}"));

    $response->assertStatus(200);
});

// 測試刪除不存在的盤點單
it('刪除不存在的盤點單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->deleteJson(apiUrl('stock-counts/99999'));

    $response->assertStatus(404);
});

// 測試未認證刪除盤點單
it('未認證使用者刪除盤點單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->deleteJson(apiUrl("stock-counts/{$count->id}"));

    $response->assertStatus(401);
});

// ==================== 盤點明細測試 ====================

// 測試取得盤點明細
it('已認證使用者可以取得盤點明細', function () {
    $user = actingAsAuthenticatedUser();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    StockCountItem::create([
        'count_id' => $count->id,
        'product_id' => $this->product->id,
        'system_quantity' => 100,
        'counted_quantity' => 98,
        'variance_quantity' => -2,
    ]);

    $response = $this->getJson(apiUrl("stock-counts/{$count->id}/items"));

    $response->assertStatus(200);
});

// 測試未認證取得盤點明細
it('未認證使用者取得盤點明細會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("stock-counts/{$count->id}/items"));

    $response->assertStatus(401);
});

// ==================== 新增盤點項目測試 ====================

// 測試成功新增盤點項目
it('已認證使用者可以新增盤點項目', function () {
    $user = actingAsAuthenticatedUser();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->postJson(apiUrl("stock-counts/{$count->id}/items"), [
        'product_id' => $this->product->id,
        'counted_quantity' => 98,
        'remark' => '差異原因',
    ]);

    $response->assertStatus(201);
});

// 測試新增盤點項目驗證錯誤 - 缺少必填欄位
it('新增盤點項目時缺少必填欄位會回傳 422 錯誤', function () {
    $user = actingAsAuthenticatedUser();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->postJson(apiUrl("stock-counts/{$count->id}/items"), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['product_id', 'counted_quantity']);
});

// 測試新增盤點項目驗證錯誤 - 商品不存在
it('新增盤點項目時商品不存在會回傳 422 錯誤', function () {
    $user = actingAsAuthenticatedUser();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->postJson(apiUrl("stock-counts/{$count->id}/items"), [
        'product_id' => 99999,
        'counted_quantity' => 100,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['product_id']);
});

// 測試新增盤點項目驗證錯誤 - 盤點數量為負
it('新增盤點項目時盤點數量為負會回傳 422 錯誤', function () {
    $user = actingAsAuthenticatedUser();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->postJson(apiUrl("stock-counts/{$count->id}/items"), [
        'product_id' => $this->product->id,
        'counted_quantity' => -10,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['counted_quantity']);
});

// 測試未認證新增盤點項目
it('未認證使用者新增盤點項目會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->postJson(apiUrl("stock-counts/{$count->id}/items"), [
        'product_id' => $this->product->id,
        'counted_quantity' => 100,
    ]);

    $response->assertStatus(401);
});

// ==================== 更新盤點數量測試 ====================

// 測試成功更新盤點數量
it('已認證使用者可以更新盤點數量', function () {
    $user = actingAsAuthenticatedUser();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $item = StockCountItem::create([
        'count_id' => $count->id,
        'product_id' => $this->product->id,
        'system_quantity' => 100,
        'counted_quantity' => 98,
        'variance_quantity' => -2,
    ]);

    $response = $this->putJson(apiUrl("stock-counts/{$count->id}/items/{$item->id}"), [
        'counted_quantity' => 99,
        'remark' => '重新盤點',
    ]);

    $response->assertStatus(200);
});

// 測試更新盤點數量驗證錯誤 - 數量為負
it('更新盤點數量為負數會回傳 422 錯誤', function () {
    $user = actingAsAuthenticatedUser();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $item = StockCountItem::create([
        'count_id' => $count->id,
        'product_id' => $this->product->id,
        'system_quantity' => 100,
        'counted_quantity' => 98,
        'variance_quantity' => -2,
    ]);

    $response = $this->putJson(apiUrl("stock-counts/{$count->id}/items/{$item->id}"), [
        'counted_quantity' => -5,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['counted_quantity']);
});

// 測試更新不存在的盤點項目
it('更新不存在的盤點項目會回傳 404 錯誤', function () {
    $user = actingAsAuthenticatedUser();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("stock-counts/{$count->id}/items/99999"), [
        'counted_quantity' => 100,
    ]);

    $response->assertStatus(404);
});

// 測試未認證更新盤點數量
it('未認證使用者更新盤點數量會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $item = StockCountItem::create([
        'count_id' => $count->id,
        'product_id' => $this->product->id,
        'system_quantity' => 100,
        'counted_quantity' => 98,
        'variance_quantity' => -2,
    ]);

    $response = $this->putJson(apiUrl("stock-counts/{$count->id}/items/{$item->id}"), [
        'counted_quantity' => 99,
    ]);

    $response->assertStatus(401);
});

// ==================== 完成盤點測試 ====================

// 測試完成盤點
it('已認證使用者可以完成盤點', function () {
    $user = actingAsAuthenticatedUser();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'COUNTING',
        'created_by' => $user->id,
    ]);

    StockCountItem::create([
        'count_id' => $count->id,
        'product_id' => $this->product->id,
        'system_quantity' => 100,
        'counted_quantity' => 98,
        'variance_quantity' => -2,
    ]);

    $response = $this->putJson(apiUrl("stock-counts/{$count->id}/complete"));

    $response->assertStatus(200);
});

// 測試完成不存在的盤點單
it('完成不存在的盤點單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('stock-counts/99999/complete'));

    $response->assertStatus(404);
});

// 測試未認證完成盤點
it('未認證使用者完成盤點會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $count = StockCount::create([
        'count_no' => 'CNT202401010001',
        'warehouse_id' => $this->warehouse->id,
        'count_date' => now(),
        'count_type' => 'FULL',
        'status' => 'PENDING',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("stock-counts/{$count->id}/complete"));

    $response->assertStatus(401);
});
