<?php

/**
 * 商品規格 API 測試
 *
 * 測試商品規格（SKU）的 CRUD 操作
 */

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
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
});

// ==================== 新增規格測試 ====================

// 測試成功新增商品規格
it('已認證使用者可以新增商品規格', function () {
    actingAsAuthenticatedUser();

    $variantData = [
        'sku' => 'PROD001-RED-L',
        'barcode' => '4710123456789',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
        'cost_price' => 50,
        'selling_price' => 120,
        'status' => 'ACTIVE',
    ];

    $response = $this->postJson(apiUrl("products/{$this->product->id}/variants"), $variantData);

    $response->assertStatus(201)
        ->assertJsonPath('data.sku', 'PROD001-RED-L')
        ->assertJsonPath('data.product_id', $this->product->id);

    $this->assertDatabaseHas('product_variants', [
        'sku' => 'PROD001-RED-L',
        'product_id' => $this->product->id,
    ]);
});

// 測試新增規格驗證錯誤 - 缺少必填欄位
it('新增規格時缺少必填欄位會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/variants"), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['sku', 'variant_options']);
});

// 測試新增規格驗證錯誤 - SKU 重複
it('新增規格時 SKU 重複會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
    ]);

    $response = $this->postJson(apiUrl("products/{$this->product->id}/variants"), [
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'blue', 'size' => 'M'],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['sku']);
});

// 測試新增規格驗證錯誤 - 條碼重複
it('新增規格時條碼重複會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'PROD001-RED-L',
        'barcode' => '4710123456789',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
    ]);

    $response = $this->postJson(apiUrl("products/{$this->product->id}/variants"), [
        'sku' => 'PROD001-BLUE-M',
        'barcode' => '4710123456789',
        'variant_options' => ['color' => 'blue', 'size' => 'M'],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['barcode']);
});

// 測試新增規格驗證錯誤 - 成本價為負數
it('新增規格時成本價為負數會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/variants"), [
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
        'cost_price' => -50,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['cost_price']);
});

// 測試新增規格驗證錯誤 - 售價為負數
it('新增規格時售價為負數會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/variants"), [
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
        'selling_price' => -100,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['selling_price']);
});

// 測試新增規格驗證錯誤 - 狀態無效
it('新增規格時狀態無效會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/variants"), [
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
        'status' => 'INVALID_STATUS',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

// 測試未認證新增規格
it('未認證使用者新增規格會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl("products/{$this->product->id}/variants"), [
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
    ]);

    $response->assertStatus(401);
});

// 測試新增規格到不存在的商品
it('新增規格到不存在的商品會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('products/99999/variants'), [
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
    ]);

    $response->assertStatus(404);
});

// ==================== 更新規格測試 ====================

// 測試成功更新商品規格
it('已認證使用者可以更新商品規格', function () {
    actingAsAuthenticatedUser();

    $variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("products/{$this->product->id}/variants/{$variant->id}"), [
        'sku' => 'PROD001-BLUE-XL',
        'variant_options' => ['color' => 'blue', 'size' => 'XL'],
        'selling_price' => 150,
        'status' => 'INACTIVE',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.sku', 'PROD001-BLUE-XL')
        ->assertJsonPath('data.selling_price', '150.00')
        ->assertJsonPath('data.status', 'INACTIVE');

    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'sku' => 'PROD001-BLUE-XL',
        'selling_price' => 150,
    ]);
});

// 測試更新規格部分欄位
it('可以只更新規格的部分欄位', function () {
    actingAsAuthenticatedUser();

    $variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("products/{$this->product->id}/variants/{$variant->id}"), [
        'selling_price' => 200,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.sku', 'PROD001-RED-L')
        ->assertJsonPath('data.selling_price', '200.00');
});

// 測試更新規格驗證錯誤 - SKU 重複
it('更新規格時 SKU 與其他規格重複會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
    ]);

    $variant2 = ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'PROD001-BLUE-M',
        'variant_options' => ['color' => 'blue', 'size' => 'M'],
    ]);

    $response = $this->putJson(apiUrl("products/{$this->product->id}/variants/{$variant2->id}"), [
        'sku' => 'PROD001-RED-L',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['sku']);
});

// 測試更新不屬於該商品的規格
it('更新不屬於該商品的規格會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    // 建立另一個商品
    $anotherProduct = Product::create([
        'sku' => 'PROD002',
        'name' => '另一個商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 200,
        'status' => 'ACTIVE',
    ]);

    // 在另一個商品上建立規格
    $variant = ProductVariant::create([
        'product_id' => $anotherProduct->id,
        'sku' => 'PROD002-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
    ]);

    // 嘗試用第一個商品的路徑更新第二個商品的規格
    $response = $this->putJson(apiUrl("products/{$this->product->id}/variants/{$variant->id}"), [
        'sku' => 'PROD001-NEW',
    ]);

    $response->assertStatus(404);
});

// 測試更新不存在的規格
it('更新不存在的規格會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl("products/{$this->product->id}/variants/99999"), [
        'sku' => 'PROD001-NEW',
    ]);

    $response->assertStatus(404);
});

// 測試未認證更新規格
it('未認證使用者更新規格會回傳 401 錯誤', function () {
    $variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
    ]);

    $response = $this->putJson(apiUrl("products/{$this->product->id}/variants/{$variant->id}"), [
        'sku' => 'PROD001-NEW',
    ]);

    $response->assertStatus(401);
});

// ==================== 刪除規格測試 ====================

// 測試成功刪除商品規格
it('已認證使用者可以刪除沒有庫存的商品規格', function () {
    actingAsAuthenticatedUser();

    $variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
    ]);

    $response = $this->deleteJson(apiUrl("products/{$this->product->id}/variants/{$variant->id}"));

    $response->assertStatus(200);

    $this->assertDatabaseMissing('product_variants', [
        'id' => $variant->id,
    ]);
});

// 測試刪除有庫存的規格
it('刪除有庫存的規格會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    // 建立門市和倉庫
    $store = Store::create([
        'code' => 'STORE001',
        'name' => '測試門市',
        'status' => 'ACTIVE',
    ]);

    $warehouse = Warehouse::create([
        'code' => 'WH001',
        'name' => '測試倉庫',
        'type' => 'WAREHOUSE',
        'store_id' => $store->id,
        'status' => 'ACTIVE',
    ]);

    $variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
    ]);

    // 建立庫存記錄（數量大於 0）
    Inventory::create([
        'product_id' => $this->product->id,
        'variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
    ]);

    $response = $this->deleteJson(apiUrl("products/{$this->product->id}/variants/{$variant->id}"));

    $response->assertStatus(422);

    // 確保規格未被刪除
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
    ]);
});

// 測試刪除有訂單關聯的規格
it('刪除有訂單關聯的規格會回傳 422 錯誤', function () {
    $user = actingAsAuthenticatedUser();

    // 建立門市
    $store = Store::create([
        'code' => 'STORE001',
        'name' => '測試門市',
        'status' => 'ACTIVE',
    ]);

    $variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
    ]);

    // 建立訂單
    $order = Order::create([
        'order_no' => 'ORD001',
        'store_id' => $store->id,
        'cashier_id' => $user->id,
        'order_date' => now(),
        'subtotal' => 100,
        'total_amount' => 100,
        'status' => 'COMPLETED',
    ]);

    // 建立訂單明細
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'variant_id' => $variant->id,
        'product_name' => $this->product->name,
        'sku' => $variant->sku,
        'quantity' => 1,
        'unit_price' => 100,
        'original_price' => 100,
        'subtotal' => 100,
    ]);

    $response = $this->deleteJson(apiUrl("products/{$this->product->id}/variants/{$variant->id}"));

    $response->assertStatus(422);

    // 確保規格未被刪除
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
    ]);
});

// 測試刪除不屬於該商品的規格
it('刪除不屬於該商品的規格會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    // 建立另一個商品
    $anotherProduct = Product::create([
        'sku' => 'PROD002',
        'name' => '另一個商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 200,
        'status' => 'ACTIVE',
    ]);

    // 在另一個商品上建立規格
    $variant = ProductVariant::create([
        'product_id' => $anotherProduct->id,
        'sku' => 'PROD002-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
    ]);

    // 嘗試用第一個商品的路徑刪除第二個商品的規格
    $response = $this->deleteJson(apiUrl("products/{$this->product->id}/variants/{$variant->id}"));

    $response->assertStatus(404);
});

// 測試刪除不存在的規格
it('刪除不存在的規格會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->deleteJson(apiUrl("products/{$this->product->id}/variants/99999"));

    $response->assertStatus(404);
});

// 測試未認證刪除規格
it('未認證使用者刪除規格會回傳 401 錯誤', function () {
    $variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
    ]);

    $response = $this->deleteJson(apiUrl("products/{$this->product->id}/variants/{$variant->id}"));

    $response->assertStatus(401);
});

// ==================== 額外驗證測試 ====================

// 測試 variant_options 必須是陣列
it('variant_options 必須是陣列格式', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/variants"), [
        'sku' => 'PROD001-RED-L',
        'variant_options' => 'not-an-array',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['variant_options']);
});

// 測試可以新增包含圖片的規格
it('可以新增包含圖片的商品規格', function () {
    actingAsAuthenticatedUser();

    $variantData = [
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
        'image_url' => 'https://example.com/images/red-l.jpg',
    ];

    $response = $this->postJson(apiUrl("products/{$this->product->id}/variants"), $variantData);

    $response->assertStatus(201)
        ->assertJsonPath('data.image_url', 'https://example.com/images/red-l.jpg');
});

// 測試庫存為 0 可以刪除規格
it('庫存為零的規格可以刪除', function () {
    actingAsAuthenticatedUser();

    // 建立門市和倉庫
    $store = Store::create([
        'code' => 'STORE001',
        'name' => '測試門市',
        'status' => 'ACTIVE',
    ]);

    $warehouse = Warehouse::create([
        'code' => 'WH001',
        'name' => '測試倉庫',
        'type' => 'WAREHOUSE',
        'store_id' => $store->id,
        'status' => 'ACTIVE',
    ]);

    $variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
    ]);

    // 建立庫存記錄（數量為 0）
    Inventory::create([
        'product_id' => $this->product->id,
        'variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 0,
        'reserved_quantity' => 0,
    ]);

    $response = $this->deleteJson(apiUrl("products/{$this->product->id}/variants/{$variant->id}"));

    $response->assertStatus(200);

    $this->assertDatabaseMissing('product_variants', [
        'id' => $variant->id,
    ]);
});
