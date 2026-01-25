<?php

/**
 * 收銀班別 API 測試
 *
 * 測試收銀班別的開班、關班、交接操作
 */

use App\Models\CashierShift;
use App\Models\Store;
use App\Models\User;

// ==================== 測試前置作業 ====================

beforeEach(function () {
    // 建立預設門市
    $this->store = Store::create([
        'code' => 'S001',
        'name' => '測試門市',
        'status' => 'ACTIVE',
    ]);
});

/**
 * 建立測試用班別
 */
function createCashierShift($store, $user, $status = 'OPEN', $startTime = null): CashierShift
{
    return CashierShift::create([
        'store_id' => $store->id,
        'pos_id' => 'POS001',
        'cashier_id' => $user->id,
        'shift_date' => today(),
        'start_time' => $startTime ?? now(),
        'end_time' => $status === 'CLOSED' ? now() : null,
        'opening_cash' => 5000,
        'expected_cash' => 5000,
        'actual_cash' => $status === 'CLOSED' ? 5000 : null,
        'cash_difference' => $status === 'CLOSED' ? 0 : null,
        'total_sales' => 0,
        'total_refunds' => 0,
        'total_transactions' => 0,
        'status' => $status,
    ]);
}

// ==================== 列表測試 ====================

// 測試取得班別列表
it('已認證使用者可以取得班別列表', function () {
    $user = actingAsAuthenticatedUser();
    createCashierShift($this->store, $user);

    $response = $this->getJson(apiUrl('cashier-shifts'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'store_id',
                    'pos_id',
                    'cashier_id',
                    'shift_date',
                    'start_time',
                    'opening_cash',
                    'expected_cash',
                    'status',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

// 測試未認證取得班別列表
it('未認證使用者取得班別列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('cashier-shifts'));

    $response->assertStatus(401);
});

// 測試依門市篩選班別
it('可以透過門市篩選班別', function () {
    $user = actingAsAuthenticatedUser();

    $store2 = Store::create(['code' => 'S002', 'name' => '門市二', 'status' => 'ACTIVE']);

    createCashierShift($this->store, $user);
    createCashierShift($store2, $user);

    $response = $this->getJson(apiUrl("cashier-shifts?store_id={$this->store->id}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試依收銀員篩選班別
it('可以透過收銀員篩選班別', function () {
    $user1 = actingAsAuthenticatedUser();
    $user2 = User::factory()->create();

    createCashierShift($this->store, $user1);

    // 需要手動建立另一個使用者的班別
    CashierShift::create([
        'store_id' => $this->store->id,
        'pos_id' => 'POS002',
        'cashier_id' => $user2->id,
        'shift_date' => today(),
        'start_time' => now(),
        'opening_cash' => 3000,
        'expected_cash' => 3000,
        'total_sales' => 0,
        'total_refunds' => 0,
        'total_transactions' => 0,
        'status' => 'OPEN',
    ]);

    $response = $this->getJson(apiUrl("cashier-shifts?cashier_id={$user1->id}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試依狀態篩選班別
it('可以透過狀態篩選班別', function () {
    $user = actingAsAuthenticatedUser();

    createCashierShift($this->store, $user, 'OPEN');

    $user2 = User::factory()->create();
    CashierShift::create([
        'store_id' => $this->store->id,
        'pos_id' => 'POS002',
        'cashier_id' => $user2->id,
        'shift_date' => today(),
        'start_time' => now()->subHours(8),
        'end_time' => now(),
        'opening_cash' => 5000,
        'expected_cash' => 8000,
        'actual_cash' => 8000,
        'cash_difference' => 0,
        'total_sales' => 3000,
        'total_refunds' => 0,
        'total_transactions' => 10,
        'status' => 'CLOSED',
    ]);

    $response = $this->getJson(apiUrl('cashier-shifts?status=OPEN'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試日期範圍篩選
it('可以透過日期範圍篩選班別', function () {
    $user = actingAsAuthenticatedUser();

    createCashierShift($this->store, $user);

    // 建立過去的班別
    CashierShift::create([
        'store_id' => $this->store->id,
        'pos_id' => 'POS001',
        'cashier_id' => $user->id,
        'shift_date' => today()->subDays(5),
        'start_time' => now()->subDays(5),
        'opening_cash' => 5000,
        'expected_cash' => 5000,
        'total_sales' => 0,
        'total_refunds' => 0,
        'total_transactions' => 0,
        'status' => 'CLOSED',
    ]);

    $startDate = now()->subDays(2)->toDateString();
    $endDate = now()->addDay()->toDateString();

    $response = $this->getJson(apiUrl("cashier-shifts?start_date={$startDate}&end_date={$endDate}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試分頁功能
it('班別列表支援分頁', function () {
    $user = actingAsAuthenticatedUser();

    for ($i = 1; $i <= 20; $i++) {
        CashierShift::create([
            'store_id' => $this->store->id,
            'pos_id' => 'POS'.str_pad($i, 3, '0', STR_PAD_LEFT),
            'cashier_id' => $user->id,
            'shift_date' => today()->subDays($i),
            'start_time' => now()->subDays($i),
            'opening_cash' => 5000,
            'expected_cash' => 5000,
            'total_sales' => 0,
            'total_refunds' => 0,
            'total_transactions' => 0,
            'status' => 'CLOSED',
        ]);
    }

    $response = $this->getJson(apiUrl('cashier-shifts?per_page=5&page=1'));

    $response->assertStatus(200);
    $this->assertEquals(5, count($response->json('data')));
    $this->assertEquals(20, $response->json('meta.total'));
    $this->assertEquals(4, $response->json('meta.last_page'));
});

// ==================== 查看單一班別測試 ====================

// 測試取得單一班別
it('已認證使用者可以取得單一班別詳情', function () {
    $user = actingAsAuthenticatedUser();
    $shift = createCashierShift($this->store, $user);

    $response = $this->getJson(apiUrl("cashier-shifts/{$shift->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $shift->id)
        ->assertJsonPath('data.status', 'OPEN')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'store_id',
                'pos_id',
                'cashier_id',
                'shift_date',
                'start_time',
                'opening_cash',
                'expected_cash',
                'status',
                'store',
                'cashier',
            ],
        ]);
});

// 測試進行中的班別包含即時統計
it('進行中的班別詳情包含即時統計資料', function () {
    $user = actingAsAuthenticatedUser();
    $shift = createCashierShift($this->store, $user, 'OPEN');

    $response = $this->getJson(apiUrl("cashier-shifts/{$shift->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'OPEN')
        ->assertJsonStructure([
            'data' => [
                'current_stats',
            ],
        ]);
});

// 測試取得不存在的班別
it('取得不存在的班別會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('cashier-shifts/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一班別
it('未認證使用者取得單一班別會回傳 401 錯誤', function () {
    $user = User::factory()->create();
    $shift = createCashierShift($this->store, $user);

    $response = $this->getJson(apiUrl("cashier-shifts/{$shift->id}"));

    $response->assertStatus(401);
});

// ==================== 開班測試 ====================

// 測試成功開班
it('已認證使用者可以開班', function () {
    $user = actingAsAuthenticatedUser();

    $shiftData = [
        'store_id' => $this->store->id,
        'register_no' => 'POS001',
        'opening_cash' => 5000,
        'remark' => '早班開始',
    ];

    $response = $this->postJson(apiUrl('cashier-shifts'), $shiftData);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'OPEN')
        ->assertJsonPath('data.opening_cash', '5000.00')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'store_id',
                'pos_id',
                'cashier_id',
                'shift_date',
                'start_time',
                'opening_cash',
                'status',
                'store',
                'cashier',
            ],
        ]);

    $this->assertDatabaseHas('cashier_shifts', [
        'store_id' => $this->store->id,
        'pos_id' => 'POS001',
        'cashier_id' => $user->id,
        'status' => 'OPEN',
    ]);
});

// 測試已有進行中班別無法開班
it('已有進行中班別無法再次開班', function () {
    $user = actingAsAuthenticatedUser();

    // 建立進行中的班別
    createCashierShift($this->store, $user, 'OPEN');

    $response = $this->postJson(apiUrl('cashier-shifts'), [
        'store_id' => $this->store->id,
        'register_no' => 'POS002',
        'opening_cash' => 3000,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

// 測試未認證開班
it('未認證使用者開班會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl('cashier-shifts'), [
        'store_id' => $this->store->id,
        'register_no' => 'POS001',
        'opening_cash' => 5000,
    ]);

    $response->assertStatus(401);
});

// ==================== 開班驗證規則測試 ====================

// 測試門市為必填
it('開班時門市為必填欄位', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('cashier-shifts'), [
        'register_no' => 'POS001',
        'opening_cash' => 5000,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['store_id']);
});

// 測試門市必須存在
it('開班時門市必須存在', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('cashier-shifts'), [
        'store_id' => 99999,
        'register_no' => 'POS001',
        'opening_cash' => 5000,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['store_id']);
});

// 測試收銀機編號為必填
it('開班時收銀機編號為必填欄位', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('cashier-shifts'), [
        'store_id' => $this->store->id,
        'opening_cash' => 5000,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['register_no']);
});

// 測試期初現金為必填
it('開班時期初現金為必填欄位', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('cashier-shifts'), [
        'store_id' => $this->store->id,
        'register_no' => 'POS001',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['opening_cash']);
});

// 測試期初現金不能為負數
it('開班時期初現金不能為負數', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('cashier-shifts'), [
        'store_id' => $this->store->id,
        'register_no' => 'POS001',
        'opening_cash' => -100,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['opening_cash']);
});

// ==================== 關班測試 ====================

// 測試成功關班
it('已認證使用者可以關閉自己的班別', function () {
    $user = actingAsAuthenticatedUser();
    $shift = createCashierShift($this->store, $user, 'OPEN');

    $response = $this->putJson(apiUrl("cashier-shifts/{$shift->id}/close"), [
        'actual_cash' => 5000,
        'difference_note' => '無差異',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'CLOSED')
        ->assertJsonPath('data.actual_cash', '5000.00');

    $this->assertDatabaseHas('cashier_shifts', [
        'id' => $shift->id,
        'status' => 'CLOSED',
    ]);
});

// 測試關班需要實際現金
it('關班時必須提供實際現金金額', function () {
    $user = actingAsAuthenticatedUser();
    $shift = createCashierShift($this->store, $user, 'OPEN');

    $response = $this->putJson(apiUrl("cashier-shifts/{$shift->id}/close"), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['actual_cash']);
});

// 測試無法關閉他人班別
it('無法關閉他人的班別', function () {
    $user1 = actingAsAuthenticatedUser();
    $user2 = User::factory()->create();

    // 建立 user2 的班別
    $shift = CashierShift::create([
        'store_id' => $this->store->id,
        'pos_id' => 'POS001',
        'cashier_id' => $user2->id,
        'shift_date' => today(),
        'start_time' => now(),
        'opening_cash' => 5000,
        'expected_cash' => 5000,
        'total_sales' => 0,
        'total_refunds' => 0,
        'total_transactions' => 0,
        'status' => 'OPEN',
    ]);

    $response = $this->putJson(apiUrl("cashier-shifts/{$shift->id}/close"), [
        'actual_cash' => 5000,
    ]);

    $response->assertStatus(403);
});

// 測試無法關閉已關閉的班別
it('無法關閉已關閉的班別', function () {
    $user = actingAsAuthenticatedUser();
    $shift = createCashierShift($this->store, $user, 'CLOSED');

    $response = $this->putJson(apiUrl("cashier-shifts/{$shift->id}/close"), [
        'actual_cash' => 5000,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

// 測試未認證關班
it('未認證使用者關班會回傳 401 錯誤', function () {
    $user = User::factory()->create();
    $shift = createCashierShift($this->store, $user, 'OPEN');

    $response = $this->putJson(apiUrl("cashier-shifts/{$shift->id}/close"), [
        'actual_cash' => 5000,
    ]);

    $response->assertStatus(401);
});

// 測試關班時現金差異計算
it('關班時正確計算現金差異', function () {
    $user = actingAsAuthenticatedUser();
    $shift = createCashierShift($this->store, $user, 'OPEN');

    $response = $this->putJson(apiUrl("cashier-shifts/{$shift->id}/close"), [
        'actual_cash' => 4800,
        'difference_note' => '短少 200 元',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.actual_cash', '4800.00')
        ->assertJsonPath('data.difference_note', '短少 200 元');
});

// ==================== 關班驗證規則測試 ====================

// 測試實際現金不能為負數
it('關班時實際現金不能為負數', function () {
    $user = actingAsAuthenticatedUser();
    $shift = createCashierShift($this->store, $user, 'OPEN');

    $response = $this->putJson(apiUrl("cashier-shifts/{$shift->id}/close"), [
        'actual_cash' => -100,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['actual_cash']);
});

// 測試差異備註長度限制
it('差異備註不能超過 255 字元', function () {
    $user = actingAsAuthenticatedUser();
    $shift = createCashierShift($this->store, $user, 'OPEN');

    $response = $this->putJson(apiUrl("cashier-shifts/{$shift->id}/close"), [
        'actual_cash' => 5000,
        'difference_note' => str_repeat('測試', 200),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['difference_note']);
});

// ==================== 排序測試 ====================

// 測試排序功能
it('可以根據指定欄位排序班別', function () {
    $user = actingAsAuthenticatedUser();

    CashierShift::create([
        'store_id' => $this->store->id,
        'pos_id' => 'POS001',
        'cashier_id' => $user->id,
        'shift_date' => today(),
        'start_time' => now()->subHours(2),
        'opening_cash' => 3000,
        'expected_cash' => 3000,
        'total_sales' => 0,
        'total_refunds' => 0,
        'total_transactions' => 0,
        'status' => 'OPEN',
    ]);

    CashierShift::create([
        'store_id' => $this->store->id,
        'pos_id' => 'POS002',
        'cashier_id' => $user->id,
        'shift_date' => today(),
        'start_time' => now(),
        'opening_cash' => 8000,
        'expected_cash' => 8000,
        'total_sales' => 0,
        'total_refunds' => 0,
        'total_transactions' => 0,
        'status' => 'OPEN',
    ]);

    $response = $this->getJson(apiUrl('cashier-shifts?sort_by=opening_cash&sort_order=desc'));

    $response->assertStatus(200);
    $shifts = $response->json('data');
    $this->assertEquals('8000.00', $shifts[0]['opening_cash']);
});

// ==================== 班別關聯資料測試 ====================

// 測試班別列表包含門市和收銀員資訊
it('班別列表包含門市和收銀員資訊', function () {
    $user = actingAsAuthenticatedUser();
    createCashierShift($this->store, $user);

    $response = $this->getJson(apiUrl('cashier-shifts'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'store',
                    'cashier',
                ],
            ],
        ]);
});

// 測試關班不存在的班別
it('關班不存在的班別會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('cashier-shifts/99999/close'), [
        'actual_cash' => 5000,
    ]);

    $response->assertStatus(404);
});
