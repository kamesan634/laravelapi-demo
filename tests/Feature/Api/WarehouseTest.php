<?php

/**
 * 倉庫 API 測試
 *
 * 測試倉庫資料的 CRUD 操作
 */

use App\Models\Store;
use App\Models\Warehouse;

// ==================== 列表測試 ====================

// 測試取得倉庫列表
it('已認證使用者可以取得倉庫列表', function () {
    actingAsAuthenticatedUser();

    $store = Store::create(['code' => 'S001', 'name' => '門市', 'status' => 'ACTIVE']);

    Warehouse::create([
        'code' => 'W001',
        'name' => '主倉庫',
        'type' => 'WAREHOUSE',
        'status' => 'ACTIVE',
    ]);
    Warehouse::create([
        'code' => 'W002',
        'name' => '門市倉',
        'type' => 'STORE',
        'store_id' => $store->id,
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('warehouses'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => ['id', 'code', 'name', 'type', 'status', 'created_at', 'updated_at'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

// 測試未認證取得倉庫列表
it('未認證使用者取得倉庫列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('warehouses'));

    $response->assertStatus(401);
});

// 測試搜尋倉庫列表
it('可以透過關鍵字搜尋倉庫', function () {
    actingAsAuthenticatedUser();

    Warehouse::create(['code' => 'W001', 'name' => '主倉庫', 'type' => 'WAREHOUSE', 'status' => 'ACTIVE']);
    Warehouse::create(['code' => 'W002', 'name' => '備用倉', 'type' => 'WAREHOUSE', 'status' => 'ACTIVE']);

    $response = $this->getJson(apiUrl('warehouses?keyword=主倉'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試類型篩選
it('可以透過類型篩選倉庫', function () {
    actingAsAuthenticatedUser();

    Warehouse::create(['code' => 'W001', 'name' => '獨立倉庫', 'type' => 'WAREHOUSE', 'status' => 'ACTIVE']);
    Warehouse::create(['code' => 'W002', 'name' => '門市倉庫', 'type' => 'STORE', 'status' => 'ACTIVE']);

    $response = $this->getJson(apiUrl('warehouses?type=WAREHOUSE'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試門市篩選
it('可以透過門市篩選倉庫', function () {
    actingAsAuthenticatedUser();

    $store1 = Store::create(['code' => 'S001', 'name' => '門市一', 'status' => 'ACTIVE']);
    $store2 = Store::create(['code' => 'S002', 'name' => '門市二', 'status' => 'ACTIVE']);

    Warehouse::create(['code' => 'W001', 'name' => '門市一倉', 'type' => 'STORE', 'store_id' => $store1->id, 'status' => 'ACTIVE']);
    Warehouse::create(['code' => 'W002', 'name' => '門市二倉', 'type' => 'STORE', 'store_id' => $store2->id, 'status' => 'ACTIVE']);

    $response = $this->getJson(apiUrl("warehouses?store_id={$store1->id}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// ==================== 新增測試 ====================

// 測試成功新增獨立倉庫
it('已認證使用者可以新增獨立倉庫', function () {
    actingAsAuthenticatedUser();

    $warehouseData = [
        'code' => 'W001',
        'name' => '新倉庫',
        'type' => 'WAREHOUSE',
        'address' => '台北市中山區',
        'contact_person' => '張先生',
        'phone' => '02-12345678',
        'status' => 'ACTIVE',
    ];

    $response = $this->postJson(apiUrl('warehouses'), $warehouseData);

    $response->assertStatus(201)
        ->assertJsonPath('data.code', 'W001')
        ->assertJsonPath('data.name', '新倉庫')
        ->assertJsonPath('data.type', 'WAREHOUSE');

    $this->assertDatabaseHas('warehouses', [
        'code' => 'W001',
        'name' => '新倉庫',
    ]);
});

// 測試成功新增門市倉庫
it('已認證使用者可以新增門市倉庫', function () {
    actingAsAuthenticatedUser();

    $store = Store::create(['code' => 'S001', 'name' => '測試門市', 'status' => 'ACTIVE']);

    $warehouseData = [
        'code' => 'W001',
        'name' => '門市倉庫',
        'type' => 'STORE',
        'store_id' => $store->id,
        'status' => 'ACTIVE',
    ];

    $response = $this->postJson(apiUrl('warehouses'), $warehouseData);

    $response->assertStatus(201)
        ->assertJsonPath('data.type', 'STORE')
        ->assertJsonPath('data.store_id', $store->id);
});

// 測試新增倉庫驗證錯誤 - 缺少必填欄位
it('新增倉庫時缺少必填欄位會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('warehouses'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code', 'name', 'type']);
});

// 測試新增倉庫驗證錯誤 - 代碼重複
it('新增倉庫時代碼重複會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    Warehouse::create(['code' => 'W001', 'name' => '現有倉庫', 'type' => 'WAREHOUSE', 'status' => 'ACTIVE']);

    $response = $this->postJson(apiUrl('warehouses'), [
        'code' => 'W001',
        'name' => '新倉庫',
        'type' => 'WAREHOUSE',
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

// 測試新增倉庫驗證錯誤 - 類型無效
it('新增倉庫時類型無效會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('warehouses'), [
        'code' => 'W001',
        'name' => '新倉庫',
        'type' => 'INVALID_TYPE',
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

// 測試未認證新增倉庫
it('未認證使用者新增倉庫會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl('warehouses'), [
        'code' => 'W001',
        'name' => '新倉庫',
        'type' => 'WAREHOUSE',
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(401);
});

// ==================== 查看單一倉庫測試 ====================

// 測試取得單一倉庫
it('已認證使用者可以取得單一倉庫詳情', function () {
    actingAsAuthenticatedUser();

    $warehouse = Warehouse::create([
        'code' => 'W001',
        'name' => '測試倉庫',
        'type' => 'WAREHOUSE',
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("warehouses/{$warehouse->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $warehouse->id)
        ->assertJsonPath('data.code', 'W001')
        ->assertJsonPath('data.name', '測試倉庫');
});

// 測試取得不存在的倉庫
it('取得不存在的倉庫會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('warehouses/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一倉庫
it('未認證使用者取得單一倉庫會回傳 401 錯誤', function () {
    $warehouse = Warehouse::create([
        'code' => 'W001',
        'name' => '測試倉庫',
        'type' => 'WAREHOUSE',
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("warehouses/{$warehouse->id}"));

    $response->assertStatus(401);
});

// ==================== 更新測試 ====================

// 測試成功更新倉庫
it('已認證使用者可以更新倉庫', function () {
    actingAsAuthenticatedUser();

    $warehouse = Warehouse::create([
        'code' => 'W001',
        'name' => '舊名稱',
        'type' => 'WAREHOUSE',
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("warehouses/{$warehouse->id}"), [
        'name' => '新名稱',
        'contact_person' => '新聯絡人',
        'status' => 'INACTIVE',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', '新名稱')
        ->assertJsonPath('data.contact_person', '新聯絡人')
        ->assertJsonPath('data.status', 'INACTIVE');

    $this->assertDatabaseHas('warehouses', [
        'id' => $warehouse->id,
        'name' => '新名稱',
        'status' => 'INACTIVE',
    ]);
});

// 測試更新倉庫驗證錯誤
it('更新倉庫時驗證錯誤會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $warehouse = Warehouse::create([
        'code' => 'W001',
        'name' => '測試倉庫',
        'type' => 'WAREHOUSE',
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("warehouses/{$warehouse->id}"), [
        'status' => 'INVALID_STATUS',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

// 測試更新不存在的倉庫
it('更新不存在的倉庫會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('warehouses/99999'), [
        'name' => '新名稱',
    ]);

    $response->assertStatus(404);
});

// 測試未認證更新倉庫
it('未認證使用者更新倉庫會回傳 401 錯誤', function () {
    $warehouse = Warehouse::create([
        'code' => 'W001',
        'name' => '測試倉庫',
        'type' => 'WAREHOUSE',
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("warehouses/{$warehouse->id}"), [
        'name' => '新名稱',
    ]);

    $response->assertStatus(401);
});

// ==================== 刪除測試 ====================

// 測試成功刪除倉庫
it('已認證使用者可以刪除沒有庫存的倉庫', function () {
    actingAsAuthenticatedUser();

    $warehouse = Warehouse::create([
        'code' => 'W001',
        'name' => '測試倉庫',
        'type' => 'WAREHOUSE',
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("warehouses/{$warehouse->id}"));

    $response->assertStatus(200);

    $this->assertDatabaseMissing('warehouses', [
        'id' => $warehouse->id,
    ]);
});

// 測試刪除不存在的倉庫
it('刪除不存在的倉庫會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->deleteJson(apiUrl('warehouses/99999'));

    $response->assertStatus(404);
});

// 測試未認證刪除倉庫
it('未認證使用者刪除倉庫會回傳 401 錯誤', function () {
    $warehouse = Warehouse::create([
        'code' => 'W001',
        'name' => '測試倉庫',
        'type' => 'WAREHOUSE',
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("warehouses/{$warehouse->id}"));

    $response->assertStatus(401);
});
