<?php

/**
 * 商品 API 測試
 *
 * 測試商品資料的 CRUD 操作
 */

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;

// ==================== 測試前置作業 ====================

beforeEach(function () {
    // 建立預設分類
    $this->category = Category::create([
        'code' => 'CAT001',
        'name' => '測試分類',
        'status' => 'ACTIVE',
    ]);
});

// ==================== 列表測試 ====================

// 測試取得商品列表
it('已認證使用者可以取得商品列表', function () {
    actingAsAuthenticatedUser();

    Product::create([
        'sku' => 'SKU001',
        'name' => '商品A',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);
    Product::create([
        'sku' => 'SKU002',
        'name' => '商品B',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 200,
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('products'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'sku',
                    'name',
                    'category_id',
                    'selling_price',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

// 測試未認證取得商品列表
it('未認證使用者取得商品列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('products'));

    $response->assertStatus(401);
});

// 測試搜尋商品列表 - 依名稱
it('可以透過名稱搜尋商品', function () {
    actingAsAuthenticatedUser();

    Product::create([
        'sku' => 'SKU001',
        'name' => '可樂',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 30,
        'status' => 'ACTIVE',
    ]);
    Product::create([
        'sku' => 'SKU002',
        'name' => '雪碧',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 30,
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('products?keyword=可樂'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試搜尋商品列表 - 依 SKU
it('可以透過 SKU 搜尋商品', function () {
    actingAsAuthenticatedUser();

    Product::create([
        'sku' => 'COLA-001',
        'name' => '商品A',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 30,
        'status' => 'ACTIVE',
    ]);
    Product::create([
        'sku' => 'SPRITE-001',
        'name' => '商品B',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 30,
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('products?keyword=COLA'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試分類篩選
it('可以透過分類篩選商品', function () {
    actingAsAuthenticatedUser();

    $category2 = Category::create(['code' => 'CAT002', 'name' => '飲料', 'status' => 'ACTIVE']);

    Product::create([
        'sku' => 'SKU001',
        'name' => '食品商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);
    Product::create([
        'sku' => 'SKU002',
        'name' => '飲料商品',
        'category_id' => $category2->id,
        'unit' => 'PCS',
        'selling_price' => 50,
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("products?category_id={$this->category->id}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試狀態篩選
it('可以透過狀態篩選商品', function () {
    actingAsAuthenticatedUser();

    Product::create([
        'sku' => 'SKU001',
        'name' => '上架商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);
    Product::create([
        'sku' => 'SKU002',
        'name' => '下架商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'INACTIVE',
    ]);

    $response = $this->getJson(apiUrl('products?status=ACTIVE'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試價格範圍篩選
it('可以透過價格範圍篩選商品', function () {
    actingAsAuthenticatedUser();

    Product::create([
        'sku' => 'SKU001',
        'name' => '便宜商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 50,
        'status' => 'ACTIVE',
    ]);
    Product::create([
        'sku' => 'SKU002',
        'name' => '貴商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 500,
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('products?min_price=100&max_price=600'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// ==================== 新增測試 ====================

// 測試成功新增商品
it('已認證使用者可以新增商品', function () {
    actingAsAuthenticatedUser();

    $productData = [
        'sku' => 'SKU001',
        'name' => '新商品',
        'short_name' => '新品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'cost_price' => 50,
        'selling_price' => 100,
        'member_price' => 90,
        'safety_stock' => 10,
        'status' => 'ACTIVE',
    ];

    $response = $this->postJson(apiUrl('products'), $productData);

    $response->assertStatus(201)
        ->assertJsonPath('data.sku', 'SKU001')
        ->assertJsonPath('data.name', '新商品')
        ->assertJsonPath('data.selling_price', '100.00');

    $this->assertDatabaseHas('products', [
        'sku' => 'SKU001',
        'name' => '新商品',
    ]);
});

// 測試新增商品驗證錯誤 - 缺少必填欄位
it('新增商品時缺少必填欄位會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('products'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['sku', 'name', 'category_id', 'unit', 'selling_price']);
});

// 測試新增商品驗證錯誤 - SKU 重複
it('新增商品時 SKU 重複會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    Product::create([
        'sku' => 'SKU001',
        'name' => '現有商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    $response = $this->postJson(apiUrl('products'), [
        'sku' => 'SKU001',
        'name' => '新商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['sku']);
});

// 測試新增商品驗證錯誤 - 條碼重複
it('新增商品時條碼重複會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    Product::create([
        'sku' => 'SKU001',
        'barcode' => '4710123456789',
        'name' => '現有商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    $response = $this->postJson(apiUrl('products'), [
        'sku' => 'SKU002',
        'barcode' => '4710123456789',
        'name' => '新商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['barcode']);
});

// 測試新增商品驗證錯誤 - 分類不存在
it('新增商品時分類不存在會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('products'), [
        'sku' => 'SKU001',
        'name' => '新商品',
        'category_id' => 99999,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['category_id']);
});

// 測試新增商品驗證錯誤 - 售價為負數
it('新增商品時售價為負數會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('products'), [
        'sku' => 'SKU001',
        'name' => '新商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => -100,
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['selling_price']);
});

// 測試新增商品驗證錯誤 - 狀態無效
it('新增商品時狀態無效會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('products'), [
        'sku' => 'SKU001',
        'name' => '新商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'INVALID_STATUS',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

// 測試未認證新增商品
it('未認證使用者新增商品會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl('products'), [
        'sku' => 'SKU001',
        'name' => '新商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(401);
});

// ==================== 查看單一商品測試 ====================

// 測試取得單一商品
it('已認證使用者可以取得單一商品詳情', function () {
    actingAsAuthenticatedUser();

    $product = Product::create([
        'sku' => 'SKU001',
        'name' => '測試商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("products/{$product->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.sku', 'SKU001')
        ->assertJsonPath('data.name', '測試商品');
});

// 測試取得不存在的商品
it('取得不存在的商品會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('products/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一商品
it('未認證使用者取得單一商品會回傳 401 錯誤', function () {
    $product = Product::create([
        'sku' => 'SKU001',
        'name' => '測試商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("products/{$product->id}"));

    $response->assertStatus(401);
});

// ==================== 更新測試 ====================

// 測試成功更新商品
it('已認證使用者可以更新商品', function () {
    actingAsAuthenticatedUser();

    $product = Product::create([
        'sku' => 'SKU001',
        'name' => '舊名稱',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("products/{$product->id}"), [
        'name' => '新名稱',
        'selling_price' => 150,
        'member_price' => 130,
        'status' => 'INACTIVE',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', '新名稱')
        ->assertJsonPath('data.selling_price', '150.00')
        ->assertJsonPath('data.status', 'INACTIVE');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => '新名稱',
        'selling_price' => 150,
    ]);
});

// 測試更新商品驗證錯誤
it('更新商品時驗證錯誤會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $product = Product::create([
        'sku' => 'SKU001',
        'name' => '測試商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("products/{$product->id}"), [
        'selling_price' => -50,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['selling_price']);
});

// 測試更新不存在的商品
it('更新不存在的商品會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('products/99999'), [
        'name' => '新名稱',
    ]);

    $response->assertStatus(404);
});

// 測試未認證更新商品
it('未認證使用者更新商品會回傳 401 錯誤', function () {
    $product = Product::create([
        'sku' => 'SKU001',
        'name' => '測試商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("products/{$product->id}"), [
        'name' => '新名稱',
    ]);

    $response->assertStatus(401);
});

// ==================== 刪除測試 ====================

// 測試成功刪除商品
it('已認證使用者可以刪除沒有庫存和訂單的商品', function () {
    actingAsAuthenticatedUser();

    $product = Product::create([
        'sku' => 'SKU001',
        'name' => '測試商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("products/{$product->id}"));

    $response->assertStatus(200);

    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});

// 測試刪除不存在的商品
it('刪除不存在的商品會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->deleteJson(apiUrl('products/99999'));

    $response->assertStatus(404);
});

// 測試未認證刪除商品
it('未認證使用者刪除商品會回傳 401 錯誤', function () {
    $product = Product::create([
        'sku' => 'SKU001',
        'name' => '測試商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("products/{$product->id}"));

    $response->assertStatus(401);
});

// ==================== 額外驗證測試 ====================

// 測試新增商品時指定供應商
it('可以新增指定供應商的商品', function () {
    actingAsAuthenticatedUser();

    $supplier = Supplier::create([
        'code' => 'SUP001',
        'name' => '供應商',
        'status' => 'ACTIVE',
    ]);

    $productData = [
        'sku' => 'SKU001',
        'name' => '新商品',
        'category_id' => $this->category->id,
        'supplier_id' => $supplier->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ];

    $response = $this->postJson(apiUrl('products'), $productData);

    $response->assertStatus(201)
        ->assertJsonPath('data.supplier_id', $supplier->id);
});

// 測試安全庫存驗證
it('安全庫存必須為非負數', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('products'), [
        'sku' => 'SKU001',
        'name' => '新商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 100,
        'safety_stock' => -5,
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['safety_stock']);
});

// 測試成本價驗證
it('成本價必須為非負數', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('products'), [
        'sku' => 'SKU001',
        'name' => '新商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'cost_price' => -50,
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['cost_price']);
});
