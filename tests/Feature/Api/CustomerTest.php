<?php

/**
 * 會員 API 測試
 *
 * 測試會員資料的 CRUD 操作
 */

use App\Models\Customer;
use App\Models\CustomerLevel;

// ==================== 測試前置作業 ====================

beforeEach(function () {
    // 建立預設會員等級
    $this->defaultLevel = CustomerLevel::create([
        'level_code' => 1,
        'name' => '一般會員',
        'spending_threshold' => 0,
        'discount_rate' => 0,
        'points_multiplier' => 1.0,
        'status' => 'ACTIVE',
    ]);
});

// ==================== 列表測試 ====================

// 測試取得會員列表
it('已認證使用者可以取得會員列表', function () {
    actingAsAuthenticatedUser();

    Customer::create([
        'member_no' => 'M001',
        'name' => '張三',
        'phone' => '0912345678',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);
    Customer::create([
        'member_no' => 'M002',
        'name' => '李四',
        'phone' => '0923456789',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('customers'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'member_no',
                    'name',
                    'phone',
                    'level_id',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

// 測試未認證取得會員列表
it('未認證使用者取得會員列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('customers'));

    $response->assertStatus(401);
});

// 測試搜尋會員列表
it('可以透過關鍵字搜尋會員', function () {
    actingAsAuthenticatedUser();

    Customer::create([
        'member_no' => 'M001',
        'name' => '張三',
        'phone' => '0912345678',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);
    Customer::create([
        'member_no' => 'M002',
        'name' => '李四',
        'phone' => '0923456789',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('customers?keyword=張三'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試透過手機號碼搜尋會員
it('可以透過手機號碼搜尋會員', function () {
    actingAsAuthenticatedUser();

    Customer::create([
        'member_no' => 'M001',
        'name' => '張三',
        'phone' => '0912345678',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('customers?keyword=0912'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試會員等級篩選
it('可以透過會員等級篩選會員', function () {
    actingAsAuthenticatedUser();

    $vipLevel = CustomerLevel::create([
        'level_code' => 2,
        'name' => 'VIP會員',
        'spending_threshold' => 10000,
        'status' => 'ACTIVE',
    ]);

    Customer::create([
        'member_no' => 'M001',
        'name' => '一般會員張三',
        'phone' => '0912345678',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);
    Customer::create([
        'member_no' => 'M002',
        'name' => 'VIP會員李四',
        'phone' => '0923456789',
        'level_id' => $vipLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("customers?level_id={$vipLevel->id}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試狀態篩選
it('可以透過狀態篩選會員', function () {
    actingAsAuthenticatedUser();

    Customer::create([
        'member_no' => 'M001',
        'name' => '啟用會員',
        'phone' => '0912345678',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);
    Customer::create([
        'member_no' => 'M002',
        'name' => '停用會員',
        'phone' => '0923456789',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'INACTIVE',
    ]);

    $response = $this->getJson(apiUrl('customers?status=ACTIVE'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// ==================== 新增測試 ====================

// 測試成功新增會員
it('已認證使用者可以新增會員', function () {
    actingAsAuthenticatedUser();

    $customerData = [
        'member_no' => 'M001',
        'name' => '新會員',
        'phone' => '0912345678',
        'email' => 'customer@example.com',
        'gender' => 'M',
        'birthday' => '1990-01-01',
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ];

    $response = $this->postJson(apiUrl('customers'), $customerData);

    $response->assertStatus(201)
        ->assertJsonPath('data.member_no', 'M001')
        ->assertJsonPath('data.name', '新會員')
        ->assertJsonPath('data.phone', '0912345678');

    $this->assertDatabaseHas('customers', [
        'member_no' => 'M001',
        'name' => '新會員',
    ]);
});

// 測試新增會員時自動指派預設等級
it('新增會員時未指定等級會自動指派預設等級', function () {
    actingAsAuthenticatedUser();

    $customerData = [
        'member_no' => 'M001',
        'name' => '新會員',
        'phone' => '0912345678',
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ];

    $response = $this->postJson(apiUrl('customers'), $customerData);

    $response->assertStatus(201)
        ->assertJsonPath('data.level_id', $this->defaultLevel->id);
});

// 測試新增會員驗證錯誤 - 缺少必填欄位
it('新增會員時缺少必填欄位會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('customers'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['member_no', 'name', 'phone', 'join_date']);
});

// 測試新增會員驗證錯誤 - 會員編號重複
it('新增會員時會員編號重複會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    Customer::create([
        'member_no' => 'M001',
        'name' => '現有會員',
        'phone' => '0911111111',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response = $this->postJson(apiUrl('customers'), [
        'member_no' => 'M001',
        'name' => '新會員',
        'phone' => '0912345678',
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['member_no']);
});

// 測試新增會員驗證錯誤 - 手機號碼重複
it('新增會員時手機號碼重複會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    Customer::create([
        'member_no' => 'M001',
        'name' => '現有會員',
        'phone' => '0912345678',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response = $this->postJson(apiUrl('customers'), [
        'member_no' => 'M002',
        'name' => '新會員',
        'phone' => '0912345678',
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['phone']);
});

// 測試新增會員驗證錯誤 - Email 格式錯誤
it('新增會員時 Email 格式錯誤會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('customers'), [
        'member_no' => 'M001',
        'name' => '新會員',
        'phone' => '0912345678',
        'email' => 'invalid-email',
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

// 測試新增會員驗證錯誤 - 性別值無效
it('新增會員時性別值無效會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('customers'), [
        'member_no' => 'M001',
        'name' => '新會員',
        'phone' => '0912345678',
        'gender' => 'INVALID',
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['gender']);
});

// 測試未認證新增會員
it('未認證使用者新增會員會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl('customers'), [
        'member_no' => 'M001',
        'name' => '新會員',
        'phone' => '0912345678',
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(401);
});

// ==================== 查看單一會員測試 ====================

// 測試取得單一會員
it('已認證使用者可以取得單一會員詳情', function () {
    actingAsAuthenticatedUser();

    $customer = Customer::create([
        'member_no' => 'M001',
        'name' => '測試會員',
        'phone' => '0912345678',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("customers/{$customer->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $customer->id)
        ->assertJsonPath('data.member_no', 'M001')
        ->assertJsonPath('data.name', '測試會員');
});

// 測試取得不存在的會員
it('取得不存在的會員會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('customers/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一會員
it('未認證使用者取得單一會員會回傳 401 錯誤', function () {
    $customer = Customer::create([
        'member_no' => 'M001',
        'name' => '測試會員',
        'phone' => '0912345678',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("customers/{$customer->id}"));

    $response->assertStatus(401);
});

// ==================== 更新測試 ====================

// 測試成功更新會員
it('已認證使用者可以更新會員', function () {
    actingAsAuthenticatedUser();

    $customer = Customer::create([
        'member_no' => 'M001',
        'name' => '舊名稱',
        'phone' => '0912345678',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("customers/{$customer->id}"), [
        'name' => '新名稱',
        'email' => 'updated@example.com',
        'address' => '台北市信義區',
        'status' => 'INACTIVE',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', '新名稱')
        ->assertJsonPath('data.email', 'updated@example.com')
        ->assertJsonPath('data.status', 'INACTIVE');

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => '新名稱',
        'status' => 'INACTIVE',
    ]);
});

// 測試更新會員驗證錯誤
it('更新會員時驗證錯誤會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $customer = Customer::create([
        'member_no' => 'M001',
        'name' => '測試會員',
        'phone' => '0912345678',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("customers/{$customer->id}"), [
        'email' => 'invalid-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

// 測試更新不存在的會員
it('更新不存在的會員會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('customers/99999'), [
        'name' => '新名稱',
    ]);

    $response->assertStatus(404);
});

// 測試未認證更新會員
it('未認證使用者更新會員會回傳 401 錯誤', function () {
    $customer = Customer::create([
        'member_no' => 'M001',
        'name' => '測試會員',
        'phone' => '0912345678',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("customers/{$customer->id}"), [
        'name' => '新名稱',
    ]);

    $response->assertStatus(401);
});

// ==================== 刪除測試 ====================

// 測試成功刪除會員
it('已認證使用者可以刪除沒有訂單的會員', function () {
    actingAsAuthenticatedUser();

    $customer = Customer::create([
        'member_no' => 'M001',
        'name' => '測試會員',
        'phone' => '0912345678',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("customers/{$customer->id}"));

    $response->assertStatus(200);

    $this->assertDatabaseMissing('customers', [
        'id' => $customer->id,
    ]);
});

// 測試刪除不存在的會員
it('刪除不存在的會員會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->deleteJson(apiUrl('customers/99999'));

    $response->assertStatus(404);
});

// 測試未認證刪除會員
it('未認證使用者刪除會員會回傳 401 錯誤', function () {
    $customer = Customer::create([
        'member_no' => 'M001',
        'name' => '測試會員',
        'phone' => '0912345678',
        'level_id' => $this->defaultLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("customers/{$customer->id}"));

    $response->assertStatus(401);
});
