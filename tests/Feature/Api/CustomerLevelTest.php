<?php

/**
 * 會員等級 API 測試
 *
 * 測試會員等級資料的 CRUD 操作
 */

use App\Models\Customer;
use App\Models\CustomerLevel;

// ==================== 列表測試 ====================

// 測試取得會員等級列表
it('已認證使用者可以取得會員等級列表', function () {
    actingAsAuthenticatedUser();

    CustomerLevel::create([
        'level_code' => 1,
        'name' => '一般會員',
        'spending_threshold' => 0,
        'discount_rate' => 0,
        'points_multiplier' => 1.0,
        'status' => 'ACTIVE',
    ]);
    CustomerLevel::create([
        'level_code' => 2,
        'name' => 'VIP會員',
        'spending_threshold' => 10000,
        'discount_rate' => 5,
        'points_multiplier' => 1.5,
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('customer-levels'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'level_code',
                    'name',
                    'spending_threshold',
                    'discount_rate',
                    'points_multiplier',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

// 測試未認證取得會員等級列表
it('未認證使用者取得會員等級列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('customer-levels'));

    $response->assertStatus(401);
});

// 測試搜尋會員等級列表
it('可以透過關鍵字搜尋會員等級', function () {
    actingAsAuthenticatedUser();

    CustomerLevel::create([
        'level_code' => 1,
        'name' => '一般會員',
        'spending_threshold' => 0,
        'status' => 'ACTIVE',
    ]);
    CustomerLevel::create([
        'level_code' => 2,
        'name' => 'VIP會員',
        'spending_threshold' => 10000,
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('customer-levels?keyword=VIP'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試狀態篩選
it('可以透過狀態篩選會員等級', function () {
    actingAsAuthenticatedUser();

    CustomerLevel::create([
        'level_code' => 1,
        'name' => '啟用等級',
        'spending_threshold' => 0,
        'status' => 'ACTIVE',
    ]);
    CustomerLevel::create([
        'level_code' => 2,
        'name' => '停用等級',
        'spending_threshold' => 10000,
        'status' => 'INACTIVE',
    ]);

    $response = $this->getJson(apiUrl('customer-levels?status=ACTIVE'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// ==================== 新增測試 ====================

// 測試成功新增會員等級
it('已認證使用者可以新增會員等級', function () {
    actingAsAuthenticatedUser();

    $levelData = [
        'level_code' => 1,
        'name' => '新等級',
        'spending_threshold' => 0,
        'discount_rate' => 5,
        'points_multiplier' => 1.5,
        'status' => 'ACTIVE',
    ];

    $response = $this->postJson(apiUrl('customer-levels'), $levelData);

    $response->assertStatus(201)
        ->assertJsonPath('data.level_code', 1)
        ->assertJsonPath('data.name', '新等級')
        ->assertJsonPath('data.discount_rate', '5.00');

    $this->assertDatabaseHas('customer_levels', [
        'level_code' => 1,
        'name' => '新等級',
    ]);
});

// 測試新增會員等級驗證錯誤 - 缺少必填欄位
it('新增會員等級時缺少必填欄位會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('customer-levels'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['level_code', 'name']);
});

// 測試新增會員等級驗證錯誤 - 等級代碼重複
it('新增會員等級時等級代碼重複會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    CustomerLevel::create([
        'level_code' => 1,
        'name' => '現有等級',
        'spending_threshold' => 0,
        'status' => 'ACTIVE',
    ]);

    $response = $this->postJson(apiUrl('customer-levels'), [
        'level_code' => 1,
        'name' => '新等級',
        'spending_threshold' => 0,
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['level_code']);
});

// 測試新增會員等級驗證錯誤 - 折扣率超出範圍
it('新增會員等級時折扣率超出範圍會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('customer-levels'), [
        'level_code' => 1,
        'name' => '新等級',
        'spending_threshold' => 0,
        'discount_rate' => 150, // 超過 100%
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['discount_rate']);
});

// 測試新增會員等級驗證錯誤 - 消費門檻為負數
it('新增會員等級時消費門檻為負數會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('customer-levels'), [
        'level_code' => 1,
        'name' => '新等級',
        'spending_threshold' => -1000,
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['spending_threshold']);
});

// 測試未認證新增會員等級
it('未認證使用者新增會員等級會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl('customer-levels'), [
        'level_code' => 1,
        'name' => '新等級',
        'spending_threshold' => 0,
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(401);
});

// ==================== 查看單一會員等級測試 ====================

// 測試取得單一會員等級
it('已認證使用者可以取得單一會員等級詳情', function () {
    actingAsAuthenticatedUser();

    $level = CustomerLevel::create([
        'level_code' => 1,
        'name' => '測試等級',
        'spending_threshold' => 0,
        'discount_rate' => 10,
        'points_multiplier' => 1.5,
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("customer-levels/{$level->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $level->id)
        ->assertJsonPath('data.level_code', 1)
        ->assertJsonPath('data.name', '測試等級');
});

// 測試取得不存在的會員等級
it('取得不存在的會員等級會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('customer-levels/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一會員等級
it('未認證使用者取得單一會員等級會回傳 401 錯誤', function () {
    $level = CustomerLevel::create([
        'level_code' => 1,
        'name' => '測試等級',
        'spending_threshold' => 0,
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("customer-levels/{$level->id}"));

    $response->assertStatus(401);
});

// ==================== 更新測試 ====================

// 測試成功更新會員等級
it('已認證使用者可以更新會員等級', function () {
    actingAsAuthenticatedUser();

    $level = CustomerLevel::create([
        'level_code' => 1,
        'name' => '舊名稱',
        'spending_threshold' => 0,
        'discount_rate' => 5,
        'points_multiplier' => 1.0,
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("customer-levels/{$level->id}"), [
        'name' => '新名稱',
        'discount_rate' => 10,
        'points_multiplier' => 2.0,
        'status' => 'INACTIVE',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', '新名稱')
        ->assertJsonPath('data.discount_rate', '10.00')
        ->assertJsonPath('data.status', 'INACTIVE');

    $this->assertDatabaseHas('customer_levels', [
        'id' => $level->id,
        'name' => '新名稱',
        'status' => 'INACTIVE',
    ]);
});

// 測試更新會員等級驗證錯誤
it('更新會員等級時驗證錯誤會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $level = CustomerLevel::create([
        'level_code' => 1,
        'name' => '測試等級',
        'spending_threshold' => 0,
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("customer-levels/{$level->id}"), [
        'discount_rate' => 200, // 超過 100%
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['discount_rate']);
});

// 測試更新不存在的會員等級
it('更新不存在的會員等級會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('customer-levels/99999'), [
        'name' => '新名稱',
    ]);

    $response->assertStatus(404);
});

// 測試未認證更新會員等級
it('未認證使用者更新會員等級會回傳 401 錯誤', function () {
    $level = CustomerLevel::create([
        'level_code' => 1,
        'name' => '測試等級',
        'spending_threshold' => 0,
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("customer-levels/{$level->id}"), [
        'name' => '新名稱',
    ]);

    $response->assertStatus(401);
});

// ==================== 刪除測試 ====================

// 測試成功刪除會員等級
it('已認證使用者可以刪除沒有關聯會員的等級', function () {
    actingAsAuthenticatedUser();

    $level = CustomerLevel::create([
        'level_code' => 1,
        'name' => '測試等級',
        'spending_threshold' => 0,
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("customer-levels/{$level->id}"));

    $response->assertStatus(200);

    $this->assertDatabaseMissing('customer_levels', [
        'id' => $level->id,
    ]);
});

// 測試刪除有關聯會員的等級
it('刪除有關聯會員的等級會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $level = CustomerLevel::create([
        'level_code' => 1,
        'name' => '測試等級',
        'spending_threshold' => 0,
        'status' => 'ACTIVE',
    ]);

    // 建立關聯會員
    Customer::create([
        'member_no' => 'M001',
        'name' => '測試會員',
        'phone' => '0912345678',
        'level_id' => $level->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("customer-levels/{$level->id}"));

    $response->assertStatus(422);
});

// 測試刪除不存在的會員等級
it('刪除不存在的會員等級會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->deleteJson(apiUrl('customer-levels/99999'));

    $response->assertStatus(404);
});

// 測試未認證刪除會員等級
it('未認證使用者刪除會員等級會回傳 401 錯誤', function () {
    $level = CustomerLevel::create([
        'level_code' => 1,
        'name' => '測試等級',
        'spending_threshold' => 0,
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("customer-levels/{$level->id}"));

    $response->assertStatus(401);
});
