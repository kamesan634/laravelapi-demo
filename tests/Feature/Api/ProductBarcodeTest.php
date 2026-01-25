<?php

/**
 * 商品條碼 API 測試
 *
 * 測試商品條碼（一品多碼）的管理操作
 */

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductVariant;

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

// ==================== 新增條碼測試 ====================

// 測試成功新增商品條碼
it('已認證使用者可以新增商品條碼', function () {
    actingAsAuthenticatedUser();

    $barcodeData = [
        'barcode' => '4710123456789',
        'barcode_type' => 'EAN13',
        'is_primary' => false,
    ];

    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), $barcodeData);

    $response->assertStatus(201)
        ->assertJsonPath('data.barcode', '4710123456789')
        ->assertJsonPath('data.product_id', $this->product->id)
        ->assertJsonPath('data.barcode_type', 'EAN13');

    $this->assertDatabaseHas('product_barcodes', [
        'barcode' => '4710123456789',
        'product_id' => $this->product->id,
    ]);
});

// 測試新增主要條碼
it('可以新增主要條碼', function () {
    actingAsAuthenticatedUser();

    $barcodeData = [
        'barcode' => '4710123456789',
        'barcode_type' => 'EAN13',
        'is_primary' => true,
    ];

    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), $barcodeData);

    $response->assertStatus(201)
        ->assertJsonPath('data.is_primary', true);

    $this->assertDatabaseHas('product_barcodes', [
        'barcode' => '4710123456789',
        'is_primary' => true,
    ]);
});

// 測試新增主要條碼會取消原有主要條碼
it('新增主要條碼會取消原有主要條碼的主要狀態', function () {
    actingAsAuthenticatedUser();

    // 先建立一個主要條碼
    $existingBarcode = ProductBarcode::create([
        'product_id' => $this->product->id,
        'barcode' => '4710111111111',
        'barcode_type' => 'EAN13',
        'is_primary' => true,
    ]);

    // 新增另一個主要條碼
    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => '4710222222222',
        'barcode_type' => 'EAN13',
        'is_primary' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.is_primary', true);

    // 確認原有主要條碼已被取消
    $existingBarcode->refresh();
    expect($existingBarcode->is_primary)->toBeFalse();
});

// 測試新增條碼關聯到規格
it('可以新增關聯到規格的條碼', function () {
    actingAsAuthenticatedUser();

    // 建立商品規格
    $variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'PROD001-RED-L',
        'variant_options' => ['color' => 'red', 'size' => 'L'],
    ]);

    $barcodeData = [
        'barcode' => '4710123456789',
        'variant_id' => $variant->id,
        'barcode_type' => 'EAN13',
        'is_primary' => false,
    ];

    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), $barcodeData);

    $response->assertStatus(201)
        ->assertJsonPath('data.variant_id', $variant->id);

    $this->assertDatabaseHas('product_barcodes', [
        'barcode' => '4710123456789',
        'variant_id' => $variant->id,
    ]);
});

// 測試新增條碼驗證錯誤 - 缺少必填欄位
it('新增條碼時缺少必填欄位會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['barcode']);
});

// 測試新增條碼驗證錯誤 - 條碼重複
it('新增條碼時條碼重複會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    ProductBarcode::create([
        'product_id' => $this->product->id,
        'barcode' => '4710123456789',
        'barcode_type' => 'EAN13',
        'is_primary' => false,
    ]);

    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => '4710123456789',
        'barcode_type' => 'EAN13',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['barcode']);
});

// 測試新增條碼驗證錯誤 - 條碼太長
it('新增條碼時條碼超過最大長度會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => str_repeat('1', 51), // 超過 50 字元
        'barcode_type' => 'EAN13',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['barcode']);
});

// 測試新增條碼驗證錯誤 - 無效的條碼類型
it('新增條碼時條碼類型無效會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => '4710123456789',
        'barcode_type' => 'INVALID_TYPE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['barcode_type']);
});

// 測試新增條碼驗證錯誤 - 規格不存在
it('新增條碼時規格不存在會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => '4710123456789',
        'variant_id' => 99999,
        'barcode_type' => 'EAN13',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['variant_id']);
});

// 測試未認證新增條碼
it('未認證使用者新增條碼會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => '4710123456789',
        'barcode_type' => 'EAN13',
    ]);

    $response->assertStatus(401);
});

// 測試新增條碼到不存在的商品
it('新增條碼到不存在的商品會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('products/99999/barcodes'), [
        'barcode' => '4710123456789',
        'barcode_type' => 'EAN13',
    ]);

    $response->assertStatus(404);
});

// ==================== 刪除條碼測試 ====================

// 測試成功刪除商品條碼
it('已認證使用者可以刪除非主要條碼', function () {
    actingAsAuthenticatedUser();

    $barcode = ProductBarcode::create([
        'product_id' => $this->product->id,
        'barcode' => '4710123456789',
        'barcode_type' => 'EAN13',
        'is_primary' => false,
    ]);

    $response = $this->deleteJson(apiUrl("products/{$this->product->id}/barcodes/{$barcode->id}"));

    $response->assertStatus(200);

    $this->assertDatabaseMissing('product_barcodes', [
        'id' => $barcode->id,
    ]);
});

// 測試刪除主要條碼
it('刪除主要條碼會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $barcode = ProductBarcode::create([
        'product_id' => $this->product->id,
        'barcode' => '4710123456789',
        'barcode_type' => 'EAN13',
        'is_primary' => true,
    ]);

    $response = $this->deleteJson(apiUrl("products/{$this->product->id}/barcodes/{$barcode->id}"));

    $response->assertStatus(422);

    // 確保條碼未被刪除
    $this->assertDatabaseHas('product_barcodes', [
        'id' => $barcode->id,
    ]);
});

// 測試刪除不屬於該商品的條碼
it('刪除不屬於該商品的條碼會回傳 404 錯誤', function () {
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

    // 在另一個商品上建立條碼
    $barcode = ProductBarcode::create([
        'product_id' => $anotherProduct->id,
        'barcode' => '4710123456789',
        'barcode_type' => 'EAN13',
        'is_primary' => false,
    ]);

    // 嘗試用第一個商品的路徑刪除第二個商品的條碼
    $response = $this->deleteJson(apiUrl("products/{$this->product->id}/barcodes/{$barcode->id}"));

    $response->assertStatus(404);
});

// 測試刪除不存在的條碼
it('刪除不存在的條碼會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->deleteJson(apiUrl("products/{$this->product->id}/barcodes/99999"));

    $response->assertStatus(404);
});

// 測試未認證刪除條碼
it('未認證使用者刪除條碼會回傳 401 錯誤', function () {
    $barcode = ProductBarcode::create([
        'product_id' => $this->product->id,
        'barcode' => '4710123456789',
        'barcode_type' => 'EAN13',
        'is_primary' => false,
    ]);

    $response = $this->deleteJson(apiUrl("products/{$this->product->id}/barcodes/{$barcode->id}"));

    $response->assertStatus(401);
});

// ==================== 各種條碼類型測試 ====================

// 測試各種條碼類型
it('支援 EAN13 條碼類型', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => '4710123456789',
        'barcode_type' => 'EAN13',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.barcode_type', 'EAN13');
});

it('支援 EAN8 條碼類型', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => '47101234',
        'barcode_type' => 'EAN8',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.barcode_type', 'EAN8');
});

it('支援 UPCA 條碼類型', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => '123456789012',
        'barcode_type' => 'UPCA',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.barcode_type', 'UPCA');
});

it('支援 CODE128 條碼類型', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => 'ABC-123-XYZ',
        'barcode_type' => 'CODE128',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.barcode_type', 'CODE128');
});

it('支援 CODE39 條碼類型', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => 'ABC123',
        'barcode_type' => 'CODE39',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.barcode_type', 'CODE39');
});

it('支援 QRCODE 條碼類型', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => 'INTERNAL-001',
        'barcode_type' => 'QRCODE',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.barcode_type', 'QRCODE');
});

// ==================== 一品多碼場景測試 ====================

// 測試同一商品可以有多個條碼
it('同一商品可以有多個條碼', function () {
    actingAsAuthenticatedUser();

    // 新增第一個條碼
    $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => '4710111111111',
        'barcode_type' => 'EAN13',
        'is_primary' => true,
    ])->assertStatus(201);

    // 新增第二個條碼
    $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => '4710222222222',
        'barcode_type' => 'EAN13',
        'is_primary' => false,
    ])->assertStatus(201);

    // 新增第三個條碼
    $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => '4710333333333',
        'barcode_type' => 'EAN13',
        'is_primary' => false,
    ])->assertStatus(201);

    // 確認資料庫中有三個條碼
    $this->assertDatabaseCount('product_barcodes', 3);
});

// 測試不同商品可以用不同條碼
it('不同商品的條碼不能重複', function () {
    actingAsAuthenticatedUser();

    // 在第一個商品建立條碼
    $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => '4710123456789',
        'barcode_type' => 'EAN13',
    ])->assertStatus(201);

    // 建立第二個商品
    $product2 = Product::create([
        'sku' => 'PROD002',
        'name' => '第二個商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'selling_price' => 200,
        'status' => 'ACTIVE',
    ]);

    // 嘗試在第二個商品建立相同條碼
    $response = $this->postJson(apiUrl("products/{$product2->id}/barcodes"), [
        'barcode' => '4710123456789',
        'barcode_type' => 'EAN13',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['barcode']);
});

// 測試新增條碼時不指定類型使用預設值
it('新增條碼時可以不指定條碼類型', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => '4710123456789',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.barcode', '4710123456789');
});

// 測試新增條碼時 is_primary 預設為 false
it('新增條碼時不指定 is_primary 預設為非主要條碼', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("products/{$this->product->id}/barcodes"), [
        'barcode' => '4710123456789',
        'barcode_type' => 'EAN13',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.is_primary', false);
});
