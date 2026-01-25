<?php

/**
 * 庫存 API 測試
 *
 * 測試庫存查詢相關功能
 */

use App\Models\Category;
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
        'selling_price' => 100,
        'safety_stock' => 10,
        'status' => 'ACTIVE',
    ]);
});

// ==================== 庫存列表測試 ====================

// 測試取得庫存列表
it('已認證使用者可以取得庫存列表', function () {
    actingAsAuthenticatedUser();

    // 建立庫存資料
    Inventory::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 100,
        'reserved_quantity' => 10,
    ]);

    $response = $this->getJson(apiUrl('inventory'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'product_id',
                    'warehouse_id',
                    'quantity',
                    'reserved_quantity',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

// 測試未認證取得庫存列表
it('未認證使用者取得庫存列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('inventory'));

    $response->assertStatus(401);
});

// ==================== 庫存篩選測試 ====================

// 測試透過關鍵字搜尋庫存
it('可以透過關鍵字搜尋庫存', function () {
    actingAsAuthenticatedUser();

    $product2 = Product::create([
        'sku' => 'SKU002',
        'barcode' => '4710000000001',
        'name' => '可樂',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 30,
        'status' => 'ACTIVE',
    ]);

    Inventory::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 100,
    ]);
    Inventory::create([
        'product_id' => $product2->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 50,
    ]);

    $response = $this->getJson(apiUrl('inventory?keyword=可樂'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試透過倉庫篩選庫存
it('可以透過倉庫篩選庫存', function () {
    actingAsAuthenticatedUser();

    $warehouse2 = Warehouse::create([
        'code' => 'W002',
        'name' => '副倉庫',
        'type' => 'WAREHOUSE',
        'status' => 'ACTIVE',
    ]);

    Inventory::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 100,
    ]);
    Inventory::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $warehouse2->id,
        'quantity' => 50,
    ]);

    $response = $this->getJson(apiUrl("inventory?warehouse_id={$this->warehouse->id}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試透過商品篩選庫存
it('可以透過商品篩選庫存', function () {
    actingAsAuthenticatedUser();

    $product2 = Product::create([
        'sku' => 'SKU002',
        'name' => '商品B',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 200,
        'status' => 'ACTIVE',
    ]);

    Inventory::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 100,
    ]);
    Inventory::create([
        'product_id' => $product2->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 50,
    ]);

    $response = $this->getJson(apiUrl("inventory?product_id={$this->product->id}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試透過分類篩選庫存
it('可以透過分類篩選庫存', function () {
    actingAsAuthenticatedUser();

    $category2 = Category::create([
        'code' => 'CAT002',
        'name' => '飲料',
        'status' => 'ACTIVE',
    ]);

    $product2 = Product::create([
        'sku' => 'SKU002',
        'name' => '飲料商品',
        'category_id' => $category2->id,
        'unit' => 'PCS',
        'selling_price' => 30,
        'status' => 'ACTIVE',
    ]);

    Inventory::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 100,
    ]);
    Inventory::create([
        'product_id' => $product2->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 50,
    ]);

    $response = $this->getJson(apiUrl("inventory?category_id={$this->category->id}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試零庫存篩選
it('可以篩選零庫存商品', function () {
    actingAsAuthenticatedUser();

    $product2 = Product::create([
        'sku' => 'SKU002',
        'name' => '缺貨商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    Inventory::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 100,
    ]);
    Inventory::create([
        'product_id' => $product2->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 0,
    ]);

    $response = $this->getJson(apiUrl('inventory?zero_stock=true'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// ==================== 單一商品庫存測試 ====================

// 測試取得單一商品庫存
it('已認證使用者可以取得單一商品的庫存資訊', function () {
    actingAsAuthenticatedUser();

    $warehouse2 = Warehouse::create([
        'code' => 'W002',
        'name' => '副倉庫',
        'type' => 'WAREHOUSE',
        'status' => 'ACTIVE',
    ]);

    Inventory::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 100,
        'reserved_quantity' => 10,
    ]);
    Inventory::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $warehouse2->id,
        'quantity' => 50,
        'reserved_quantity' => 5,
    ]);

    $response = $this->getJson(apiUrl("inventory/{$this->product->id}"));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'product',
                'total_quantity',
                'total_reserved',
                'total_available',
                'inventory_by_warehouse',
            ],
        ])
        ->assertJsonPath('data.total_quantity', 150)
        ->assertJsonPath('data.total_reserved', 15)
        ->assertJsonPath('data.total_available', 135);
});

// 測試取得不存在商品的庫存
it('取得不存在商品的庫存會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('inventory/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一商品庫存
it('未認證使用者取得單一商品庫存會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl("inventory/{$this->product->id}"));

    $response->assertStatus(401);
});

// ==================== 排序測試 ====================

// 測試庫存列表排序
it('可以依指定欄位排序庫存列表', function () {
    actingAsAuthenticatedUser();

    $product2 = Product::create([
        'sku' => 'SKU002',
        'name' => '商品B',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    Inventory::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 50,
    ]);
    Inventory::create([
        'product_id' => $product2->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 100,
    ]);

    $response = $this->getJson(apiUrl('inventory?sort_by=quantity&sort_order=desc'));

    $response->assertStatus(200);
    $data = $response->json('data');
    $this->assertEquals(100, $data[0]['quantity']);
});

// ==================== 分頁測試 ====================

// 測試庫存列表分頁
it('可以自訂每頁筆數', function () {
    actingAsAuthenticatedUser();

    // 建立多筆庫存（使用不同 SKU 避免與 beforeEach 衝突）
    for ($i = 1; $i <= 5; $i++) {
        $product = Product::create([
            'sku' => "PAGE-SKU00{$i}",
            'name' => "分頁測試商品{$i}",
            'category_id' => $this->category->id,
            'unit' => 'PCS',
            'selling_price' => 100,
            'status' => 'ACTIVE',
        ]);
        Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => $i * 10,
        ]);
    }

    $response = $this->getJson(apiUrl('inventory?per_page=2'));

    $response->assertStatus(200);
    $this->assertCount(2, $response->json('data'));
    $this->assertEquals(5, $response->json('meta.total'));
});
