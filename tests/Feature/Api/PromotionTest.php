<?php

/**
 * 促銷活動 API 測試
 *
 * 測試促銷活動資料的 CRUD 操作
 */

use App\Models\Promotion;

// ==================== 列表測試 ====================

// 測試取得促銷活動列表
it('已認證使用者可以取得促銷活動列表', function () {
    actingAsAuthenticatedUser();

    Promotion::create([
        'code' => 'PROMO001',
        'name' => '春節優惠',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode(['min_amount' => 1000]),
        'discount_rules' => json_encode(['discount_rate' => 10]),
        'status' => 'ACTIVE',
    ]);
    Promotion::create([
        'code' => 'PROMO002',
        'name' => '會員特惠',
        'promotion_type' => 'SPECIAL_PRICE',
        'start_time' => now(),
        'end_time' => now()->addDays(15),
        'conditions' => json_encode(['member_only' => true]),
        'discount_rules' => json_encode(['fixed_price' => 99]),
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('promotions'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'code',
                    'name',
                    'promotion_type',
                    'start_time',
                    'end_time',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

// 測試未認證取得促銷活動列表
it('未認證使用者取得促銷活動列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('promotions'));

    $response->assertStatus(401);
});

// 測試搜尋促銷活動列表
it('可以透過關鍵字搜尋促銷活動', function () {
    actingAsAuthenticatedUser();

    Promotion::create([
        'code' => 'PROMO001',
        'name' => '春節優惠',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'ACTIVE',
    ]);
    Promotion::create([
        'code' => 'PROMO002',
        'name' => '中秋優惠',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('promotions?keyword=春節'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試促銷類型篩選
it('可以透過促銷類型篩選促銷活動', function () {
    actingAsAuthenticatedUser();

    Promotion::create([
        'code' => 'PROMO001',
        'name' => '折扣活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'ACTIVE',
    ]);
    Promotion::create([
        'code' => 'PROMO002',
        'name' => '買一送一',
        'promotion_type' => 'BUY_X_GET_Y',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('promotions?promotion_type=DISCOUNT'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試狀態篩選
it('可以透過狀態篩選促銷活動', function () {
    actingAsAuthenticatedUser();

    Promotion::create([
        'code' => 'PROMO001',
        'name' => '啟用活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'ACTIVE',
    ]);
    Promotion::create([
        'code' => 'PROMO002',
        'name' => '草稿活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'DRAFT',
    ]);

    $response = $this->getJson(apiUrl('promotions?status=ACTIVE'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試進行中篩選
it('可以篩選進行中的促銷活動', function () {
    actingAsAuthenticatedUser();

    Promotion::create([
        'code' => 'PROMO001',
        'name' => '進行中活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now()->subDay(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'ACTIVE',
    ]);
    Promotion::create([
        'code' => 'PROMO002',
        'name' => '未來活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now()->addDays(10),
        'end_time' => now()->addDays(40),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('promotions?ongoing=1'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// ==================== 新增測試 ====================

// 測試成功新增促銷活動
it('已認證使用者可以新增促銷活動', function () {
    actingAsAuthenticatedUser();

    $promotionData = [
        'code' => 'PROMO001',
        'name' => '新促銷活動',
        'description' => '這是一個新的促銷活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now()->toDateTimeString(),
        'end_time' => now()->addDays(30)->toDateTimeString(),
        'conditions' => ['min_amount' => 1000],
        'discount_rules' => ['discount_rate' => 10],
        'usage_limit_per_customer' => 1,
        'total_usage_limit' => 100,
        'stackable' => false,
        'priority' => 1,
        'status' => 'DRAFT',
    ];

    $response = $this->postJson(apiUrl('promotions'), $promotionData);

    $response->assertStatus(201)
        ->assertJsonPath('data.code', 'PROMO001')
        ->assertJsonPath('data.name', '新促銷活動')
        ->assertJsonPath('data.promotion_type', 'DISCOUNT');

    $this->assertDatabaseHas('promotions', [
        'code' => 'PROMO001',
        'name' => '新促銷活動',
    ]);
});

// 測試新增促銷活動驗證錯誤 - 缺少必填欄位
it('新增促銷活動時缺少必填欄位會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('promotions'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code', 'name', 'promotion_type', 'start_time', 'end_time', 'conditions', 'discount_rules']);
});

// 測試新增促銷活動驗證錯誤 - 代碼重複
it('新增促銷活動時代碼重複會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    Promotion::create([
        'code' => 'PROMO001',
        'name' => '現有活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'ACTIVE',
    ]);

    $response = $this->postJson(apiUrl('promotions'), [
        'code' => 'PROMO001',
        'name' => '新活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now()->toDateTimeString(),
        'end_time' => now()->addDays(30)->toDateTimeString(),
        'conditions' => [],
        'discount_rules' => [],
        'status' => 'DRAFT',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

// 測試新增促銷活動驗證錯誤 - 結束時間早於開始時間
it('新增促銷活動時結束時間早於開始時間會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('promotions'), [
        'code' => 'PROMO001',
        'name' => '新活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now()->addDays(10)->toDateTimeString(),
        'end_time' => now()->toDateTimeString(), // 結束時間早於開始時間
        'conditions' => [],
        'discount_rules' => [],
        'status' => 'DRAFT',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['end_time']);
});

// 測試新增促銷活動驗證錯誤 - 狀態無效
it('新增促銷活動時狀態無效會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('promotions'), [
        'code' => 'PROMO001',
        'name' => '新活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now()->toDateTimeString(),
        'end_time' => now()->addDays(30)->toDateTimeString(),
        'conditions' => [],
        'discount_rules' => [],
        'status' => 'INVALID_STATUS',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

// 測試未認證新增促銷活動
it('未認證使用者新增促銷活動會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl('promotions'), [
        'code' => 'PROMO001',
        'name' => '新活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now()->toDateTimeString(),
        'end_time' => now()->addDays(30)->toDateTimeString(),
        'conditions' => [],
        'discount_rules' => [],
        'status' => 'DRAFT',
    ]);

    $response->assertStatus(401);
});

// ==================== 查看單一促銷活動測試 ====================

// 測試取得單一促銷活動
it('已認證使用者可以取得單一促銷活動詳情', function () {
    actingAsAuthenticatedUser();

    $promotion = Promotion::create([
        'code' => 'PROMO001',
        'name' => '測試活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode(['min_amount' => 500]),
        'discount_rules' => json_encode(['discount_rate' => 15]),
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("promotions/{$promotion->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $promotion->id)
        ->assertJsonPath('data.code', 'PROMO001')
        ->assertJsonPath('data.name', '測試活動');
});

// 測試取得不存在的促銷活動
it('取得不存在的促銷活動會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('promotions/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一促銷活動
it('未認證使用者取得單一促銷活動會回傳 401 錯誤', function () {
    $promotion = Promotion::create([
        'code' => 'PROMO001',
        'name' => '測試活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("promotions/{$promotion->id}"));

    $response->assertStatus(401);
});

// ==================== 更新測試 ====================

// 測試成功更新促銷活動
it('已認證使用者可以更新促銷活動', function () {
    actingAsAuthenticatedUser();

    $promotion = Promotion::create([
        'code' => 'PROMO001',
        'name' => '舊名稱',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'DRAFT',
    ]);

    $response = $this->putJson(apiUrl("promotions/{$promotion->id}"), [
        'name' => '新名稱',
        'description' => '更新的描述',
        'priority' => 5,
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', '新名稱')
        ->assertJsonPath('data.description', '更新的描述')
        ->assertJsonPath('data.status', 'ACTIVE');

    $this->assertDatabaseHas('promotions', [
        'id' => $promotion->id,
        'name' => '新名稱',
        'status' => 'ACTIVE',
    ]);
});

// 測試更新促銷活動驗證錯誤
it('更新促銷活動時驗證錯誤會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $promotion = Promotion::create([
        'code' => 'PROMO001',
        'name' => '測試活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'DRAFT',
    ]);

    $response = $this->putJson(apiUrl("promotions/{$promotion->id}"), [
        'status' => 'INVALID_STATUS',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

// 測試更新不存在的促銷活動
it('更新不存在的促銷活動會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('promotions/99999'), [
        'name' => '新名稱',
    ]);

    $response->assertStatus(404);
});

// 測試未認證更新促銷活動
it('未認證使用者更新促銷活動會回傳 401 錯誤', function () {
    $promotion = Promotion::create([
        'code' => 'PROMO001',
        'name' => '測試活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'DRAFT',
    ]);

    $response = $this->putJson(apiUrl("promotions/{$promotion->id}"), [
        'name' => '新名稱',
    ]);

    $response->assertStatus(401);
});

// ==================== 刪除測試 ====================

// 測試成功刪除促銷活動
it('已認證使用者可以刪除未使用的促銷活動', function () {
    actingAsAuthenticatedUser();

    $promotion = Promotion::create([
        'code' => 'PROMO001',
        'name' => '測試活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'current_usage' => 0,
        'status' => 'DRAFT',
    ]);

    $response = $this->deleteJson(apiUrl("promotions/{$promotion->id}"));

    $response->assertStatus(200);

    $this->assertDatabaseMissing('promotions', [
        'id' => $promotion->id,
    ]);
});

// 測試刪除已使用的促銷活動
it('刪除已使用的促銷活動會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $promotion = Promotion::create([
        'code' => 'PROMO001',
        'name' => '測試活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'current_usage' => 10, // 已被使用
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("promotions/{$promotion->id}"));

    $response->assertStatus(422);
});

// 測試刪除不存在的促銷活動
it('刪除不存在的促銷活動會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->deleteJson(apiUrl('promotions/99999'));

    $response->assertStatus(404);
});

// 測試未認證刪除促銷活動
it('未認證使用者刪除促銷活動會回傳 401 錯誤', function () {
    $promotion = Promotion::create([
        'code' => 'PROMO001',
        'name' => '測試活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'DRAFT',
    ]);

    $response = $this->deleteJson(apiUrl("promotions/{$promotion->id}"));

    $response->assertStatus(401);
});

// ==================== 啟用/停用測試 ====================

// 測試啟用促銷活動
it('可以啟用促銷活動', function () {
    actingAsAuthenticatedUser();

    $promotion = Promotion::create([
        'code' => 'PROMO001',
        'name' => '測試活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'INACTIVE',
    ]);

    $response = $this->putJson(apiUrl("promotions/{$promotion->id}/toggle"));

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'ACTIVE');
});

// 測試停用促銷活動
it('可以停用促銷活動', function () {
    actingAsAuthenticatedUser();

    $promotion = Promotion::create([
        'code' => 'PROMO001',
        'name' => '測試活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("promotions/{$promotion->id}/toggle"));

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'INACTIVE');
});

// 測試未認證啟用/停用促銷活動
it('未認證使用者啟用/停用促銷活動會回傳 401 錯誤', function () {
    $promotion = Promotion::create([
        'code' => 'PROMO001',
        'name' => '測試活動',
        'promotion_type' => 'DISCOUNT',
        'start_time' => now(),
        'end_time' => now()->addDays(30),
        'conditions' => json_encode([]),
        'discount_rules' => json_encode([]),
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("promotions/{$promotion->id}/toggle"));

    $response->assertStatus(401);
});
