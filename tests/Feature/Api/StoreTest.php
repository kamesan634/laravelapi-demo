<?php

/**
 * 門市 API 測試
 *
 * 測試門市資料的 CRUD 操作
 */

use App\Models\Store;

// ==================== 列表測試 ====================

// 測試取得門市列表
it('已認證使用者可以取得門市列表', function () {
    actingAsAuthenticatedUser();

    // 建立測試資料
    Store::create([
        'code' => 'S001',
        'name' => '台北總店',
        'status' => 'ACTIVE',
    ]);
    Store::create([
        'code' => 'S002',
        'name' => '台中分店',
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('stores'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => ['id', 'code', 'name', 'status', 'created_at', 'updated_at'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

// 測試未認證取得門市列表
it('未認證使用者取得門市列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('stores'));

    $response->assertStatus(401);
});

// 測試搜尋門市列表
it('可以透過關鍵字搜尋門市', function () {
    actingAsAuthenticatedUser();

    Store::create(['code' => 'S001', 'name' => '台北總店', 'status' => 'ACTIVE']);
    Store::create(['code' => 'S002', 'name' => '台中分店', 'status' => 'ACTIVE']);

    $response = $this->getJson(apiUrl('stores?keyword=台北'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試狀態篩選
it('可以透過狀態篩選門市', function () {
    actingAsAuthenticatedUser();

    Store::create(['code' => 'S001', 'name' => '營業中門市', 'status' => 'ACTIVE']);
    Store::create(['code' => 'S002', 'name' => '停業門市', 'status' => 'INACTIVE']);

    $response = $this->getJson(apiUrl('stores?status=ACTIVE'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// ==================== 新增測試 ====================

// 測試成功新增門市
it('已認證使用者可以新增門市', function () {
    actingAsAuthenticatedUser();

    $storeData = [
        'code' => 'S001',
        'name' => '新門市',
        'short_name' => '新店',
        'phone' => '02-12345678',
        'address' => '台北市信義區',
        'status' => 'ACTIVE',
    ];

    $response = $this->postJson(apiUrl('stores'), $storeData);

    $response->assertStatus(201)
        ->assertJsonPath('data.code', 'S001')
        ->assertJsonPath('data.name', '新門市');

    $this->assertDatabaseHas('stores', [
        'code' => 'S001',
        'name' => '新門市',
    ]);
});

// 測試新增門市驗證錯誤 - 缺少必填欄位
it('新增門市時缺少必填欄位會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stores'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code', 'name']);
});

// 測試新增門市驗證錯誤 - 代碼重複
it('新增門市時代碼重複會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    Store::create(['code' => 'S001', 'name' => '現有門市', 'status' => 'ACTIVE']);

    $response = $this->postJson(apiUrl('stores'), [
        'code' => 'S001',
        'name' => '新門市',
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

// 測試新增門市驗證錯誤 - 狀態值無效
it('新增門市時狀態值無效會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('stores'), [
        'code' => 'S001',
        'name' => '新門市',
        'status' => 'INVALID_STATUS',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

// 測試未認證新增門市
it('未認證使用者新增門市會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl('stores'), [
        'code' => 'S001',
        'name' => '新門市',
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(401);
});

// ==================== 查看單一門市測試 ====================

// 測試取得單一門市
it('已認證使用者可以取得單一門市詳情', function () {
    actingAsAuthenticatedUser();

    $store = Store::create([
        'code' => 'S001',
        'name' => '測試門市',
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("stores/{$store->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $store->id)
        ->assertJsonPath('data.code', 'S001')
        ->assertJsonPath('data.name', '測試門市');
});

// 測試取得不存在的門市
it('取得不存在的門市會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('stores/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一門市
it('未認證使用者取得單一門市會回傳 401 錯誤', function () {
    $store = Store::create([
        'code' => 'S001',
        'name' => '測試門市',
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("stores/{$store->id}"));

    $response->assertStatus(401);
});

// ==================== 更新測試 ====================

// 測試成功更新門市
it('已認證使用者可以更新門市', function () {
    actingAsAuthenticatedUser();

    $store = Store::create([
        'code' => 'S001',
        'name' => '舊名稱',
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("stores/{$store->id}"), [
        'name' => '新名稱',
        'status' => 'INACTIVE',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', '新名稱')
        ->assertJsonPath('data.status', 'INACTIVE');

    $this->assertDatabaseHas('stores', [
        'id' => $store->id,
        'name' => '新名稱',
        'status' => 'INACTIVE',
    ]);
});

// 測試更新門市驗證錯誤
it('更新門市時驗證錯誤會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $store = Store::create([
        'code' => 'S001',
        'name' => '測試門市',
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("stores/{$store->id}"), [
        'status' => 'INVALID_STATUS',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

// 測試更新不存在的門市
it('更新不存在的門市會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('stores/99999'), [
        'name' => '新名稱',
    ]);

    $response->assertStatus(404);
});

// 測試未認證更新門市
it('未認證使用者更新門市會回傳 401 錯誤', function () {
    $store = Store::create([
        'code' => 'S001',
        'name' => '測試門市',
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("stores/{$store->id}"), [
        'name' => '新名稱',
    ]);

    $response->assertStatus(401);
});

// ==================== 刪除測試 ====================

// 測試成功刪除門市
it('已認證使用者可以刪除沒有關聯資料的門市', function () {
    actingAsAuthenticatedUser();

    $store = Store::create([
        'code' => 'S001',
        'name' => '測試門市',
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("stores/{$store->id}"));

    $response->assertStatus(200);

    $this->assertDatabaseMissing('stores', [
        'id' => $store->id,
    ]);
});

// 測試刪除不存在的門市
it('刪除不存在的門市會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->deleteJson(apiUrl('stores/99999'));

    $response->assertStatus(404);
});

// 測試未認證刪除門市
it('未認證使用者刪除門市會回傳 401 錯誤', function () {
    $store = Store::create([
        'code' => 'S001',
        'name' => '測試門市',
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("stores/{$store->id}"));

    $response->assertStatus(401);
});
