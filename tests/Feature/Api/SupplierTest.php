<?php

/**
 * 供應商 API 測試
 *
 * 測試供應商資料的 CRUD 操作
 */

use App\Models\Supplier;

// ==================== 列表測試 ====================

// 測試取得供應商列表
it('已認證使用者可以取得供應商列表', function () {
    actingAsAuthenticatedUser();

    Supplier::create([
        'code' => 'SUP001',
        'name' => '供應商A',
        'contact_person' => '王先生',
        'phone' => '02-12345678',
        'status' => 'ACTIVE',
    ]);
    Supplier::create([
        'code' => 'SUP002',
        'name' => '供應商B',
        'contact_person' => '李先生',
        'phone' => '02-87654321',
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('suppliers'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'code',
                    'name',
                    'contact_person',
                    'phone',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

// 測試未認證取得供應商列表
it('未認證使用者取得供應商列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('suppliers'));

    $response->assertStatus(401);
});

// 測試搜尋供應商列表
it('可以透過關鍵字搜尋供應商', function () {
    actingAsAuthenticatedUser();

    Supplier::create([
        'code' => 'SUP001',
        'name' => '食品供應商',
        'status' => 'ACTIVE',
    ]);
    Supplier::create([
        'code' => 'SUP002',
        'name' => '飲料供應商',
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('suppliers?keyword=食品'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試透過聯絡人搜尋供應商
it('可以透過聯絡人搜尋供應商', function () {
    actingAsAuthenticatedUser();

    Supplier::create([
        'code' => 'SUP001',
        'name' => '供應商A',
        'contact_person' => '王經理',
        'status' => 'ACTIVE',
    ]);
    Supplier::create([
        'code' => 'SUP002',
        'name' => '供應商B',
        'contact_person' => '李經理',
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('suppliers?keyword=王經理'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試狀態篩選
it('可以透過狀態篩選供應商', function () {
    actingAsAuthenticatedUser();

    Supplier::create(['code' => 'SUP001', 'name' => '啟用供應商', 'status' => 'ACTIVE']);
    Supplier::create(['code' => 'SUP002', 'name' => '停用供應商', 'status' => 'INACTIVE']);

    $response = $this->getJson(apiUrl('suppliers?status=ACTIVE'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// ==================== 新增測試 ====================

// 測試成功新增供應商
it('已認證使用者可以新增供應商', function () {
    actingAsAuthenticatedUser();

    $supplierData = [
        'code' => 'SUP001',
        'name' => '新供應商',
        'short_name' => '新供',
        'tax_id' => '12345678',
        'contact_person' => '張經理',
        'phone' => '02-12345678',
        'fax' => '02-12345679',
        'email' => 'supplier@example.com',
        'address' => '台北市中山區',
        'payment_terms' => 'NET30',
        'currency' => 'TWD',
        'status' => 'ACTIVE',
    ];

    $response = $this->postJson(apiUrl('suppliers'), $supplierData);

    $response->assertStatus(201)
        ->assertJsonPath('data.code', 'SUP001')
        ->assertJsonPath('data.name', '新供應商')
        ->assertJsonPath('data.contact_person', '張經理');

    $this->assertDatabaseHas('suppliers', [
        'code' => 'SUP001',
        'name' => '新供應商',
    ]);
});

// 測試新增供應商驗證錯誤 - 缺少必填欄位
it('新增供應商時缺少必填欄位會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('suppliers'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code', 'name']);
});

// 測試新增供應商驗證錯誤 - 代碼重複
it('新增供應商時代碼重複會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    Supplier::create(['code' => 'SUP001', 'name' => '現有供應商', 'status' => 'ACTIVE']);

    $response = $this->postJson(apiUrl('suppliers'), [
        'code' => 'SUP001',
        'name' => '新供應商',
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

// 測試新增供應商驗證錯誤 - Email 格式錯誤
it('新增供應商時 Email 格式錯誤會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('suppliers'), [
        'code' => 'SUP001',
        'name' => '新供應商',
        'email' => 'invalid-email',
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

// 測試新增供應商驗證錯誤 - 付款條件無效
it('新增供應商時付款條件無效會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('suppliers'), [
        'code' => 'SUP001',
        'name' => '新供應商',
        'payment_terms' => 'INVALID_TERMS',
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['payment_terms']);
});

// 測試未認證新增供應商
it('未認證使用者新增供應商會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl('suppliers'), [
        'code' => 'SUP001',
        'name' => '新供應商',
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(401);
});

// ==================== 查看單一供應商測試 ====================

// 測試取得單一供應商
it('已認證使用者可以取得單一供應商詳情', function () {
    actingAsAuthenticatedUser();

    $supplier = Supplier::create([
        'code' => 'SUP001',
        'name' => '測試供應商',
        'contact_person' => '測試聯絡人',
        'phone' => '02-12345678',
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("suppliers/{$supplier->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $supplier->id)
        ->assertJsonPath('data.code', 'SUP001')
        ->assertJsonPath('data.name', '測試供應商');
});

// 測試取得不存在的供應商
it('取得不存在的供應商會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('suppliers/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一供應商
it('未認證使用者取得單一供應商會回傳 401 錯誤', function () {
    $supplier = Supplier::create([
        'code' => 'SUP001',
        'name' => '測試供應商',
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("suppliers/{$supplier->id}"));

    $response->assertStatus(401);
});

// ==================== 更新測試 ====================

// 測試成功更新供應商
it('已認證使用者可以更新供應商', function () {
    actingAsAuthenticatedUser();

    $supplier = Supplier::create([
        'code' => 'SUP001',
        'name' => '舊名稱',
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("suppliers/{$supplier->id}"), [
        'name' => '新名稱',
        'contact_person' => '新聯絡人',
        'phone' => '02-99999999',
        'payment_terms' => 'NET60',
        'status' => 'INACTIVE',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', '新名稱')
        ->assertJsonPath('data.contact_person', '新聯絡人')
        ->assertJsonPath('data.payment_terms', 'NET60')
        ->assertJsonPath('data.status', 'INACTIVE');

    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
        'name' => '新名稱',
        'status' => 'INACTIVE',
    ]);
});

// 測試更新供應商驗證錯誤
it('更新供應商時驗證錯誤會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $supplier = Supplier::create([
        'code' => 'SUP001',
        'name' => '測試供應商',
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("suppliers/{$supplier->id}"), [
        'email' => 'invalid-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

// 測試更新不存在的供應商
it('更新不存在的供應商會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('suppliers/99999'), [
        'name' => '新名稱',
    ]);

    $response->assertStatus(404);
});

// 測試未認證更新供應商
it('未認證使用者更新供應商會回傳 401 錯誤', function () {
    $supplier = Supplier::create([
        'code' => 'SUP001',
        'name' => '測試供應商',
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("suppliers/{$supplier->id}"), [
        'name' => '新名稱',
    ]);

    $response->assertStatus(401);
});

// ==================== 刪除測試 ====================

// 測試成功刪除供應商
it('已認證使用者可以刪除沒有採購單的供應商', function () {
    actingAsAuthenticatedUser();

    $supplier = Supplier::create([
        'code' => 'SUP001',
        'name' => '測試供應商',
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("suppliers/{$supplier->id}"));

    $response->assertStatus(200);

    $this->assertDatabaseMissing('suppliers', [
        'id' => $supplier->id,
    ]);
});

// 測試刪除不存在的供應商
it('刪除不存在的供應商會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->deleteJson(apiUrl('suppliers/99999'));

    $response->assertStatus(404);
});

// 測試未認證刪除供應商
it('未認證使用者刪除供應商會回傳 401 錯誤', function () {
    $supplier = Supplier::create([
        'code' => 'SUP001',
        'name' => '測試供應商',
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("suppliers/{$supplier->id}"));

    $response->assertStatus(401);
});

// ==================== 額外驗證測試 ====================

// 測試供應商銀行資訊
it('可以新增包含銀行資訊的供應商', function () {
    actingAsAuthenticatedUser();

    $supplierData = [
        'code' => 'SUP001',
        'name' => '供應商',
        'bank_name' => '台灣銀行',
        'bank_code' => '004',
        'bank_branch' => '台北分行',
        'account_name' => '供應商帳戶',
        'account_number' => '1234567890',
        'status' => 'ACTIVE',
    ];

    $response = $this->postJson(apiUrl('suppliers'), $supplierData);

    $response->assertStatus(201)
        ->assertJsonPath('data.bank_name', '台灣銀行')
        ->assertJsonPath('data.bank_code', '004')
        ->assertJsonPath('data.account_number', '1234567890');
});

// 測試最低訂購金額驗證
it('最低訂購金額必須為正數', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('suppliers'), [
        'code' => 'SUP001',
        'name' => '供應商',
        'min_order_amount' => -1000,
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['min_order_amount']);
});

// 測試免運門檻驗證
it('免運門檻必須為正數', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('suppliers'), [
        'code' => 'SUP001',
        'name' => '供應商',
        'free_shipping_threshold' => -500,
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['free_shipping_threshold']);
});
