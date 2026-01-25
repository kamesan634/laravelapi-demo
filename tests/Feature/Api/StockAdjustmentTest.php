<?php

/**
 * 庫存調整單 API 測試
 *
 * 測試庫存調整單的 CRUD 操作及審核流程
 */

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockAdjustment;
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

// 測試取得調整單列表
it('已認證使用者可以取得調整單列表', function () {
    $user = actingAsAuthenticatedUser();

    StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'PENDING',
        'reason' => '盤點差異調整',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl('stock-adjustments'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'adjustment_no',
                    'warehouse_id',
                    'adjustment_date',
                    'adjustment_type',
                    'product_id',
                    'adjust_quantity',
                    'status',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

// 測試未認證取得調整單列表
it('未認證使用者取得調整單列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('stock-adjustments'));

    $response->assertStatus(401);
});

// ==================== 篩選測試 ====================

// 測試透過關鍵字搜尋調整單
it('可以透過關鍵字搜尋調整單', function () {
    $user = actingAsAuthenticatedUser();

    StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'PENDING',
        'reason' => '盤點差異調整',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl('stock-adjustments?keyword=ADJ202401010001'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試透過倉庫篩選調整單
it('可以透過倉庫篩選調整單', function () {
    $user = actingAsAuthenticatedUser();

    $warehouse2 = Warehouse::create([
        'code' => 'W002',
        'name' => '副倉庫',
        'type' => 'WAREHOUSE',
        'status' => 'ACTIVE',
    ]);

    StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'PENDING',
        'reason' => '調整',
        'created_by' => $user->id,
    ]);
    StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010002',
        'warehouse_id' => $warehouse2->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 0,
        'adjust_quantity' => 50,
        'after_quantity' => 50,
        'status' => 'PENDING',
        'reason' => '調整',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("stock-adjustments?warehouse_id={$this->warehouse->id}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試透過調整類型篩選
it('可以透過調整類型篩選調整單', function () {
    $user = actingAsAuthenticatedUser();

    StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'PENDING',
        'reason' => '盤點調整',
        'created_by' => $user->id,
    ]);
    StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010002',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'DAMAGE',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => -5,
        'after_quantity' => 95,
        'status' => 'PENDING',
        'reason' => '損壞報廢',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl('stock-adjustments?adjustment_type=DAMAGE'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試透過狀態篩選
it('可以透過狀態篩選調整單', function () {
    $user = actingAsAuthenticatedUser();

    StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'PENDING',
        'reason' => '調整',
        'created_by' => $user->id,
    ]);
    StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010002',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 5,
        'after_quantity' => 105,
        'status' => 'APPROVED',
        'reason' => '調整',
        'created_by' => $user->id,
        'approved_by' => $user->id,
        'approved_at' => now(),
    ]);

    $response = $this->getJson(apiUrl('stock-adjustments?status=PENDING'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試日期範圍篩選
it('可以透過日期範圍篩選調整單', function () {
    $user = actingAsAuthenticatedUser();

    StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => '2024-01-01',
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'PENDING',
        'reason' => '調整',
        'created_by' => $user->id,
    ]);
    StockAdjustment::create([
        'adjustment_no' => 'ADJ202401150001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => '2024-01-15',
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 5,
        'after_quantity' => 105,
        'status' => 'PENDING',
        'reason' => '調整',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl('stock-adjustments?start_date=2024-01-10&end_date=2024-01-20'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// ==================== 新增測試 ====================

// 測試成功新增調整單（增加庫存）
it('已認證使用者可以新增調整單增加庫存', function () {
    actingAsAuthenticatedUser();

    $adjustmentData = [
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now()->toDateString(),
        'adjustment_type' => 'COUNT',
        'product_id' => $this->product->id,
        'adjust_quantity' => 10,
        'unit_cost' => 50,
        'reason' => '盤點後發現多出庫存',
    ];

    $response = $this->postJson(apiUrl('stock-adjustments'), $adjustmentData);

    $response->assertStatus(201)
        ->assertJsonPath('data.adjustment_type', 'COUNT')
        ->assertJsonPath('data.adjust_quantity', 10)
        ->assertJsonPath('data.before_quantity', 100)
        ->assertJsonPath('data.after_quantity', 110)
        ->assertJsonPath('data.status', 'PENDING');

    $this->assertDatabaseHas('stock_adjustments', [
        'adjustment_type' => 'COUNT',
        'adjust_quantity' => 10,
        'status' => 'PENDING',
    ]);
});

// 測試成功新增調整單（減少庫存）
it('已認證使用者可以新增調整單減少庫存', function () {
    actingAsAuthenticatedUser();

    $adjustmentData = [
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now()->toDateString(),
        'adjustment_type' => 'DAMAGE',
        'product_id' => $this->product->id,
        'adjust_quantity' => -20,
        'unit_cost' => 50,
        'reason' => '商品損壞報廢',
    ];

    $response = $this->postJson(apiUrl('stock-adjustments'), $adjustmentData);

    $response->assertStatus(201)
        ->assertJsonPath('data.adjust_quantity', -20)
        ->assertJsonPath('data.before_quantity', 100)
        ->assertJsonPath('data.after_quantity', 80);
});

// 測試調整後庫存為負數會失敗
it('調整後庫存為負數會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $adjustmentData = [
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now()->toDateString(),
        'adjustment_type' => 'DAMAGE',
        'product_id' => $this->product->id,
        'adjust_quantity' => -150, // 超過現有庫存
        'reason' => '損壞報廢',
    ];

    $response = $this->postJson(apiUrl('stock-adjustments'), $adjustmentData);

    $response->assertStatus(422);
});

// 測試新增調整單驗證錯誤 - 缺少必填欄位
it('新增調整單時缺少必填欄位會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stock-adjustments'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['warehouse_id', 'adjustment_date', 'adjustment_type', 'product_id', 'adjust_quantity', 'reason']);
});

// 測試新增調整單驗證錯誤 - 倉庫不存在
it('新增調整單時倉庫不存在會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stock-adjustments'), [
        'warehouse_id' => 99999,
        'adjustment_date' => now()->toDateString(),
        'adjustment_type' => 'COUNT',
        'product_id' => $this->product->id,
        'adjust_quantity' => 10,
        'reason' => '調整',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['warehouse_id']);
});

// 測試新增調整單驗證錯誤 - 商品不存在
it('新增調整單時商品不存在會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stock-adjustments'), [
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now()->toDateString(),
        'adjustment_type' => 'COUNT',
        'product_id' => 99999,
        'adjust_quantity' => 10,
        'reason' => '調整',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['product_id']);
});

// 測試新增調整單驗證錯誤 - 調整類型無效
it('新增調整單時調整類型無效會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stock-adjustments'), [
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now()->toDateString(),
        'adjustment_type' => 'INVALID_TYPE',
        'product_id' => $this->product->id,
        'adjust_quantity' => 10,
        'reason' => '調整',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['adjustment_type']);
});

// 測試未認證新增調整單
it('未認證使用者新增調整單會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl('stock-adjustments'), [
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now()->toDateString(),
        'adjustment_type' => 'COUNT',
        'product_id' => $this->product->id,
        'adjust_quantity' => 10,
        'reason' => '調整',
    ]);

    $response->assertStatus(401);
});

// ==================== 查看單一調整單測試 ====================

// 測試取得單一調整單
it('已認證使用者可以取得單一調整單詳情', function () {
    $user = actingAsAuthenticatedUser();

    $adjustment = StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'PENDING',
        'reason' => '盤點調整',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("stock-adjustments/{$adjustment->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $adjustment->id)
        ->assertJsonPath('data.adjustment_no', 'ADJ202401010001')
        ->assertJsonPath('data.adjustment_type', 'COUNT');
});

// 測試取得不存在的調整單
it('取得不存在的調整單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('stock-adjustments/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一調整單
it('未認證使用者取得單一調整單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $adjustment = StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'PENDING',
        'reason' => '盤點調整',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("stock-adjustments/{$adjustment->id}"));

    $response->assertStatus(401);
});

// ==================== 更新測試 ====================

// 測試成功更新調整單
it('已認證使用者可以更新待審核的調整單', function () {
    $user = actingAsAuthenticatedUser();

    $adjustment = StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'PENDING',
        'reason' => '舊原因',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("stock-adjustments/{$adjustment->id}"), [
        'reason' => '新原因說明',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.reason', '新原因說明');

    $this->assertDatabaseHas('stock_adjustments', [
        'id' => $adjustment->id,
        'reason' => '新原因說明',
    ]);
});

// 測試不能更新已審核的調整單
it('不能更新已審核的調整單', function () {
    $user = actingAsAuthenticatedUser();

    $adjustment = StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'APPROVED',
        'reason' => '原因',
        'created_by' => $user->id,
        'approved_by' => $user->id,
        'approved_at' => now(),
    ]);

    $response = $this->putJson(apiUrl("stock-adjustments/{$adjustment->id}"), [
        'reason' => '新原因',
    ]);

    $response->assertStatus(422);
});

// 測試更新不存在的調整單
it('更新不存在的調整單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('stock-adjustments/99999'), [
        'reason' => '新原因',
    ]);

    $response->assertStatus(404);
});

// 測試未認證更新調整單
it('未認證使用者更新調整單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $adjustment = StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'PENDING',
        'reason' => '原因',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("stock-adjustments/{$adjustment->id}"), [
        'reason' => '新原因',
    ]);

    $response->assertStatus(401);
});

// ==================== 刪除測試 ====================

// 測試成功刪除調整單
it('已認證使用者可以刪除待審核的調整單', function () {
    $user = actingAsAuthenticatedUser();

    $adjustment = StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'PENDING',
        'reason' => '原因',
        'created_by' => $user->id,
    ]);

    $response = $this->deleteJson(apiUrl("stock-adjustments/{$adjustment->id}"));

    $response->assertStatus(200);

    $this->assertDatabaseMissing('stock_adjustments', [
        'id' => $adjustment->id,
    ]);
});

// 測試不能刪除已審核的調整單
it('不能刪除已審核的調整單', function () {
    $user = actingAsAuthenticatedUser();

    $adjustment = StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'APPROVED',
        'reason' => '原因',
        'created_by' => $user->id,
        'approved_by' => $user->id,
        'approved_at' => now(),
    ]);

    $response = $this->deleteJson(apiUrl("stock-adjustments/{$adjustment->id}"));

    $response->assertStatus(422);
});

// 測試刪除不存在的調整單
it('刪除不存在的調整單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->deleteJson(apiUrl('stock-adjustments/99999'));

    $response->assertStatus(404);
});

// 測試未認證刪除調整單
it('未認證使用者刪除調整單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $adjustment = StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'PENDING',
        'reason' => '原因',
        'created_by' => $user->id,
    ]);

    $response = $this->deleteJson(apiUrl("stock-adjustments/{$adjustment->id}"));

    $response->assertStatus(401);
});

// ==================== 審核測試 ====================

// 測試成功審核調整單
it('已認證使用者可以審核待審核的調整單', function () {
    $user = actingAsAuthenticatedUser();

    $adjustment = StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'PENDING',
        'reason' => '盤點調整',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("stock-adjustments/{$adjustment->id}/approve"));

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'APPROVED');

    // 檢查庫存是否更新
    $this->assertDatabaseHas('inventory', [
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 110,
    ]);
});

// 測試審核減少庫存的調整單
it('審核減少庫存的調整單會正確更新庫存', function () {
    $user = actingAsAuthenticatedUser();

    $adjustment = StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'DAMAGE',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => -20,
        'after_quantity' => 80,
        'status' => 'PENDING',
        'reason' => '損壞報廢',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("stock-adjustments/{$adjustment->id}/approve"));

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'APPROVED');

    // 檢查庫存是否更新
    $this->assertDatabaseHas('inventory', [
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 80,
    ]);
});

// 測試不能審核已審核的調整單
it('不能審核已審核的調整單', function () {
    $user = actingAsAuthenticatedUser();

    $adjustment = StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'APPROVED',
        'reason' => '原因',
        'created_by' => $user->id,
        'approved_by' => $user->id,
        'approved_at' => now(),
    ]);

    $response = $this->putJson(apiUrl("stock-adjustments/{$adjustment->id}/approve"));

    $response->assertStatus(422);
});

// 測試審核時庫存不足會失敗
it('審核時若調整後庫存為負會回傳 422 錯誤', function () {
    $user = actingAsAuthenticatedUser();

    // 先將庫存減少
    $this->inventory->update(['quantity' => 10]);

    $adjustment = StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'DAMAGE',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100, // 舊的數量
        'adjust_quantity' => -50, // 減少超過現有庫存
        'after_quantity' => 50,
        'status' => 'PENDING',
        'reason' => '損壞報廢',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("stock-adjustments/{$adjustment->id}/approve"));

    $response->assertStatus(422);
});

// 測試審核不存在的調整單
it('審核不存在的調整單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('stock-adjustments/99999/approve'));

    $response->assertStatus(404);
});

// 測試未認證審核調整單
it('未認證使用者審核調整單會回傳 401 錯誤', function () {
    $user = \App\Models\User::factory()->create();

    $adjustment = StockAdjustment::create([
        'adjustment_no' => 'ADJ202401010001',
        'warehouse_id' => $this->warehouse->id,
        'adjustment_date' => now(),
        'adjustment_type' => 'COUNT',
        'source_type' => 'MANUAL',
        'product_id' => $this->product->id,
        'before_quantity' => 100,
        'adjust_quantity' => 10,
        'after_quantity' => 110,
        'status' => 'PENDING',
        'reason' => '原因',
        'created_by' => $user->id,
    ]);

    $response = $this->putJson(apiUrl("stock-adjustments/{$adjustment->id}/approve"));

    $response->assertStatus(401);
});
