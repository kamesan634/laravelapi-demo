<?php

/**
 * 庫存異動 API 測試
 *
 * 測試庫存異動紀錄查詢功能
 */

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Warehouse;

// ==================== 測試前置作業 ====================

beforeEach(function () {
    // 建立預設分類
    $this->category = Category::create([
        'code' => 'CAT001',
        'name' => '測試分類',
        'status' => 'ACTIVE',
    ]);

    // 建立預設商品
    $this->product = Product::create([
        'sku' => 'PROD001',
        'name' => '測試商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    // 建立門市
    $this->store = Store::create([
        'code' => 'STORE001',
        'name' => '測試門市',
        'status' => 'ACTIVE',
    ]);

    // 建立倉庫
    $this->warehouse = Warehouse::create([
        'code' => 'WH001',
        'name' => '測試倉庫',
        'type' => 'WAREHOUSE',
        'store_id' => $this->store->id,
        'status' => 'ACTIVE',
    ]);
});

// ==================== 查詢異動紀錄測試 ====================

// 測試成功查詢商品庫存異動紀錄
it('已認證使用者可以查詢商品庫存異動紀錄', function () {
    $user = actingAsAuthenticatedUser();

    // 建立異動紀錄
    InventoryMovement::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'movement_type' => 'PURCHASE_IN',
        'quantity' => 100,
        'before_quantity' => 0,
        'after_quantity' => 100,
        'unit_cost' => 50,
        'reference_type' => 'PURCHASE',
        'reference_no' => 'PO-001',
        'notes' => '進貨入庫',
        'created_by' => $user->id,
    ]);

    InventoryMovement::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'movement_type' => 'SALES_OUT',
        'quantity' => 10,
        'before_quantity' => 100,
        'after_quantity' => 90,
        'reference_type' => 'SALE',
        'reference_no' => 'SO-001',
        'notes' => '銷售出庫',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("inventory/{$this->product->id}/movements"));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'product_id',
                    'warehouse_id',
                    'movement_type',
                    'quantity',
                    'before_quantity',
                    'after_quantity',
                    'created_at',
                ],
            ],
        ]);
});

// 測試查詢空的異動紀錄
it('查詢無異動紀錄的商品會回傳空陣列', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl("inventory/{$this->product->id}/movements"));

    $response->assertStatus(200)
        ->assertJsonPath('data', []);
});

// 測試未認證查詢異動紀錄
it('未認證使用者查詢異動紀錄會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl("inventory/{$this->product->id}/movements"));

    $response->assertStatus(401);
});

// 測試查詢不存在商品的異動紀錄
it('查詢不存在商品的異動紀錄會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('inventory/99999/movements'));

    $response->assertStatus(404);
});

// ==================== 異動類型測試 ====================

// 測試查詢進貨異動紀錄
it('可以查詢進貨類型的異動紀錄', function () {
    $user = actingAsAuthenticatedUser();

    InventoryMovement::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'movement_type' => 'PURCHASE_IN',
        'quantity' => 100,
        'before_quantity' => 0,
        'after_quantity' => 100,
        'reference_type' => 'PURCHASE',
        'reference_no' => 'PO-001',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("inventory/{$this->product->id}/movements"));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBeGreaterThanOrEqual(1);
});

// 測試查詢出貨異動紀錄
it('可以查詢出貨類型的異動紀錄', function () {
    $user = actingAsAuthenticatedUser();

    InventoryMovement::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'movement_type' => 'SALES_OUT',
        'quantity' => 10,
        'before_quantity' => 100,
        'after_quantity' => 90,
        'reference_type' => 'SALE',
        'reference_no' => 'SO-001',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("inventory/{$this->product->id}/movements"));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBeGreaterThanOrEqual(1);
});

// 測試查詢調整異動紀錄
it('可以查詢調整類型的異動紀錄', function () {
    $user = actingAsAuthenticatedUser();

    InventoryMovement::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'movement_type' => 'ADJUST_OUT',
        'quantity' => -5,
        'before_quantity' => 100,
        'after_quantity' => 95,
        'reference_type' => 'ADJUSTMENT',
        'reference_no' => 'ADJ-001',
        'notes' => '盤點調整',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("inventory/{$this->product->id}/movements"));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBeGreaterThanOrEqual(1);
});

// ==================== 規格異動測試 ====================

// 測試查詢包含規格的異動紀錄
it('可以查詢包含規格的異動紀錄', function () {
    $user = actingAsAuthenticatedUser();

    $variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
    ]);

    InventoryMovement::create([
        'product_id' => $this->product->id,
        'variant_id' => $variant->id,
        'warehouse_id' => $this->warehouse->id,
        'movement_type' => 'PURCHASE_IN',
        'quantity' => 50,
        'before_quantity' => 0,
        'after_quantity' => 50,
        'reference_type' => 'PURCHASE',
        'reference_no' => 'PO-001',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("inventory/{$this->product->id}/movements"));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBeGreaterThanOrEqual(1);
});

// ==================== 多筆異動紀錄測試 ====================

// 測試查詢多筆異動紀錄
it('可以查詢多筆異動紀錄', function () {
    $user = actingAsAuthenticatedUser();

    // 建立多筆異動紀錄
    InventoryMovement::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'movement_type' => 'PURCHASE_IN',
        'quantity' => 100,
        'before_quantity' => 0,
        'after_quantity' => 100,
        'reference_type' => 'PURCHASE',
        'reference_no' => 'PO-001',
        'created_by' => $user->id,
    ]);

    InventoryMovement::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'movement_type' => 'SALES_OUT',
        'quantity' => 20,
        'before_quantity' => 100,
        'after_quantity' => 80,
        'reference_type' => 'SALE',
        'reference_no' => 'SO-001',
        'created_by' => $user->id,
    ]);

    InventoryMovement::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'movement_type' => 'PURCHASE_IN',
        'quantity' => 50,
        'before_quantity' => 80,
        'after_quantity' => 130,
        'reference_type' => 'PURCHASE',
        'reference_no' => 'PO-002',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("inventory/{$this->product->id}/movements"));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(3);
});

// ==================== 多倉庫異動測試 ====================

// 測試查詢多倉庫的異動紀錄
it('可以查詢多倉庫的異動紀錄', function () {
    $user = actingAsAuthenticatedUser();

    // 建立第二個倉庫
    $warehouse2 = Warehouse::create([
        'code' => 'WH002',
        'name' => '第二倉庫',
        'type' => 'WAREHOUSE',
        'store_id' => $this->store->id,
        'status' => 'ACTIVE',
    ]);

    // 第一個倉庫的異動
    InventoryMovement::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'movement_type' => 'PURCHASE_IN',
        'quantity' => 100,
        'before_quantity' => 0,
        'after_quantity' => 100,
        'reference_type' => 'PURCHASE',
        'reference_no' => 'PO-001',
        'created_by' => $user->id,
    ]);

    // 第二個倉庫的異動
    InventoryMovement::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $warehouse2->id,
        'movement_type' => 'TRANSFER_IN',
        'quantity' => 50,
        'before_quantity' => 0,
        'after_quantity' => 50,
        'reference_type' => 'TRANSFER',
        'reference_no' => 'TR-001',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("inventory/{$this->product->id}/movements"));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(2);
});

// ==================== 調撥異動測試 ====================

// 測試查詢調撥異動紀錄
it('可以查詢調撥類型的異動紀錄', function () {
    $user = actingAsAuthenticatedUser();

    // 建立第二個倉庫
    $warehouse2 = Warehouse::create([
        'code' => 'WH002',
        'name' => '第二倉庫',
        'type' => 'WAREHOUSE',
        'store_id' => $this->store->id,
        'status' => 'ACTIVE',
    ]);

    // 調出（從第一倉庫）
    InventoryMovement::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'movement_type' => 'TRANSFER_OUT',
        'quantity' => 30,
        'before_quantity' => 100,
        'after_quantity' => 70,
        'reference_type' => 'TRANSFER',
        'reference_no' => 'TR-001',
        'notes' => '調撥出庫',
        'created_by' => $user->id,
    ]);

    // 調入（到第二倉庫）
    InventoryMovement::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $warehouse2->id,
        'movement_type' => 'TRANSFER_IN',
        'quantity' => 30,
        'before_quantity' => 0,
        'after_quantity' => 30,
        'reference_type' => 'TRANSFER',
        'reference_no' => 'TR-001',
        'notes' => '調撥入庫',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("inventory/{$this->product->id}/movements"));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(2);
});

// ==================== 異動紀錄欄位驗證 ====================

// 測試異動紀錄包含必要欄位
it('異動紀錄包含必要欄位', function () {
    $user = actingAsAuthenticatedUser();

    InventoryMovement::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'movement_type' => 'PURCHASE_IN',
        'quantity' => 100,
        'before_quantity' => 0,
        'after_quantity' => 100,
        'unit_cost' => 50.00,
        'reference_type' => 'PURCHASE',
        'reference_no' => 'PO-001',
        'notes' => '測試進貨',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("inventory/{$this->product->id}/movements"));

    $response->assertStatus(200);
    $data = $response->json('data.0');

    expect($data)->toHaveKeys([
        'id',
        'product_id',
        'warehouse_id',
        'movement_type',
        'quantity',
        'before_quantity',
        'after_quantity',
    ]);
});

// ==================== 不同商品異動隔離測試 ====================

// 測試只查詢特定商品的異動紀錄
it('只查詢特定商品的異動紀錄', function () {
    $user = actingAsAuthenticatedUser();

    // 建立第二個商品
    $product2 = Product::create([
        'sku' => 'PROD002',
        'name' => '第二個商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 200,
        'status' => 'ACTIVE',
    ]);

    // 第一個商品的異動
    InventoryMovement::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'movement_type' => 'PURCHASE_IN',
        'quantity' => 100,
        'before_quantity' => 0,
        'after_quantity' => 100,
        'reference_type' => 'PURCHASE',
        'reference_no' => 'PO-001',
        'created_by' => $user->id,
    ]);

    // 第二個商品的異動
    InventoryMovement::create([
        'product_id' => $product2->id,
        'warehouse_id' => $this->warehouse->id,
        'movement_type' => 'PURCHASE_IN',
        'quantity' => 50,
        'before_quantity' => 0,
        'after_quantity' => 50,
        'reference_type' => 'PURCHASE',
        'reference_no' => 'PO-002',
        'created_by' => $user->id,
    ]);

    // 查詢第一個商品的異動
    $response = $this->getJson(apiUrl("inventory/{$this->product->id}/movements"));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1);
    expect($data[0]['product_id'])->toBe($this->product->id);
});

// ==================== 退貨異動測試 ====================

// 測試查詢退貨異動紀錄
it('可以查詢退貨類型的異動紀錄', function () {
    $user = actingAsAuthenticatedUser();

    InventoryMovement::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'movement_type' => 'RETURN_IN',
        'quantity' => 5,
        'before_quantity' => 90,
        'after_quantity' => 95,
        'reference_type' => 'REFUND',
        'reference_no' => 'RF-001',
        'notes' => '客戶退貨入庫',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("inventory/{$this->product->id}/movements"));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBeGreaterThanOrEqual(1);
});

// ==================== 盤點異動測試 ====================

// 測試查詢盤點異動紀錄
it('可以查詢盤點類型的異動紀錄', function () {
    $user = actingAsAuthenticatedUser();

    InventoryMovement::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'movement_type' => 'COUNT_OUT',
        'quantity' => -3,
        'before_quantity' => 100,
        'after_quantity' => 97,
        'reference_type' => 'STOCK_COUNT',
        'reference_no' => 'SC-001',
        'notes' => '盤點盤虧',
        'created_by' => $user->id,
    ]);

    $response = $this->getJson(apiUrl("inventory/{$this->product->id}/movements"));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBeGreaterThanOrEqual(1);
});
