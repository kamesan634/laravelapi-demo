<?php

/**
 * 點數紀錄 API 測試
 *
 * 測試會員點數的查詢與調整功能
 */

use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\PointsLog;
use App\Models\Store;

// ==================== 測試前置作業 ====================

beforeEach(function () {
    // 建立預設門市
    $this->store = Store::create([
        'code' => 'S001',
        'name' => '測試門市',
        'status' => 'ACTIVE',
    ]);

    // 建立預設會員等級
    $this->customerLevel = CustomerLevel::create([
        'level_code' => 1,
        'name' => '一般會員',
        'spending_threshold' => 0,
        'status' => 'ACTIVE',
    ]);

    // 建立預設會員
    $this->customer = Customer::create([
        'member_no' => 'M001',
        'name' => '測試會員',
        'phone' => '0912345678',
        'level_id' => $this->customerLevel->id,
        'join_date' => now()->toDateString(),
        'join_store_id' => $this->store->id,
        'available_points' => 500,
        'total_points' => 1000,
        'status' => 'ACTIVE',
    ]);
});

/**
 * 建立測試用點數紀錄
 */
function createPointsLog($customer, $user, $type = 'EARN', $points = 100): PointsLog
{
    $balance = $customer->available_points + $points;

    return PointsLog::create([
        'customer_id' => $customer->id,
        'type' => $type,
        'points' => $points,
        'balance' => $balance,
        'reference_type' => 'MANUAL',
        'reference_id' => null,
        'description' => '手動調整點數',
        'created_by' => $user->id,
    ]);
}

// ==================== 查詢點數紀錄測試 ====================

// 測試查詢會員點數紀錄
it('已認證使用者可以查詢會員點數紀錄', function () {
    $user = actingAsAuthenticatedUser();
    createPointsLog($this->customer, $user);

    $response = $this->getJson(apiUrl("customers/{$this->customer->id}/points"));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'customer_id',
                    'type',
                    'points',
                    'balance',
                    'reference_type',
                    'description',
                    'created_at',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

// 測試未認證查詢點數紀錄
it('未認證使用者查詢點數紀錄會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl("customers/{$this->customer->id}/points"));

    $response->assertStatus(401);
});

// 測試查詢不存在會員的點數紀錄
it('查詢不存在會員的點數紀錄會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('customers/99999/points'));

    $response->assertStatus(404);
});

// 測試依異動類型篩選
it('可以透過異動類型篩選點數紀錄', function () {
    $user = actingAsAuthenticatedUser();

    createPointsLog($this->customer, $user, 'EARN', 100);
    createPointsLog($this->customer, $user, 'REDEEM', -50);

    $response = $this->getJson(apiUrl("customers/{$this->customer->id}/points?type=EARN"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試日期範圍篩選
it('可以透過日期範圍篩選點數紀錄', function () {
    $user = actingAsAuthenticatedUser();

    // 建立 5 天前的紀錄
    PointsLog::create([
        'customer_id' => $this->customer->id,
        'type' => 'EARN',
        'points' => 100,
        'balance' => 100,
        'reference_type' => 'MANUAL',
        'description' => '手動調整點數',
        'created_by' => $user->id,
        'created_at' => now()->subDays(5),
        'updated_at' => now()->subDays(5),
    ]);

    // 建立今天的紀錄
    createPointsLog($this->customer, $user, 'EARN', 200);

    $startDate = now()->subDays(2)->toDateString();
    $endDate = now()->addDay()->toDateString();

    $response = $this->getJson(apiUrl("customers/{$this->customer->id}/points?start_date={$startDate}&end_date={$endDate}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試分頁功能
it('點數紀錄列表支援分頁', function () {
    $user = actingAsAuthenticatedUser();

    for ($i = 1; $i <= 20; $i++) {
        PointsLog::create([
            'customer_id' => $this->customer->id,
            'type' => 'EARN',
            'points' => 10 * $i,
            'balance' => 500 + 10 * $i,
            'reference_type' => 'MANUAL',
            'description' => "第 {$i} 筆紀錄",
            'created_by' => $user->id,
        ]);
    }

    $response = $this->getJson(apiUrl("customers/{$this->customer->id}/points?per_page=5&page=1"));

    $response->assertStatus(200);
    $this->assertEquals(5, count($response->json('data')));
    $this->assertEquals(20, $response->json('meta.total'));
    $this->assertEquals(4, $response->json('meta.last_page'));
});

// 測試點數紀錄按建立時間降序排列
it('點數紀錄按建立時間降序排列', function () {
    $user = actingAsAuthenticatedUser();

    $log1 = createPointsLog($this->customer, $user, 'EARN', 100);
    $log1->update(['created_at' => now()->subMinutes(10)]);

    $log2 = createPointsLog($this->customer, $user, 'BONUS', 200);

    $response = $this->getJson(apiUrl("customers/{$this->customer->id}/points"));

    $response->assertStatus(200);
    $logs = $response->json('data');

    // 最新的紀錄應該排在前面
    $this->assertEquals('BONUS', $logs[0]['type']);
    $this->assertEquals('EARN', $logs[1]['type']);
});

// 測試點數紀錄包含建立者資訊
it('點數紀錄包含建立者資訊', function () {
    $user = actingAsAuthenticatedUser();
    createPointsLog($this->customer, $user);

    $response = $this->getJson(apiUrl("customers/{$this->customer->id}/points"));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'creator',
                ],
            ],
        ]);
});

// ==================== 手動調整點數測試 ====================

// 測試成功增加點數（EARN 類型）
it('已認證使用者可以為會員增加點數', function () {
    $user = actingAsAuthenticatedUser();
    $originalPoints = $this->customer->available_points;

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'EARN',
        'points' => 100,
        'description' => '消費獎勵點數',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.type', 'EARN')
        ->assertJsonPath('data.points', 100)
        ->assertJsonPath('data.balance', $originalPoints + 100);

    $this->assertDatabaseHas('points_logs', [
        'customer_id' => $this->customer->id,
        'type' => 'EARN',
        'points' => 100,
    ]);

    // 確認會員點數已更新
    $this->customer->refresh();
    $this->assertEquals($originalPoints + 100, $this->customer->available_points);
});

// 測試成功扣除點數（REDEEM 類型）
it('已認證使用者可以為會員扣除點數', function () {
    $user = actingAsAuthenticatedUser();
    $originalPoints = $this->customer->available_points;

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'REDEEM',
        'points' => 100,
        'description' => '兌換商品',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.type', 'REDEEM')
        ->assertJsonPath('data.points', -100) // REDEEM 類型會轉為負數
        ->assertJsonPath('data.balance', $originalPoints - 100);

    // 確認會員點數已更新
    $this->customer->refresh();
    $this->assertEquals($originalPoints - 100, $this->customer->available_points);
});

// 測試成功調整點數（ADJUST 類型 - 正數）
it('可以用 ADJUST 類型增加點數', function () {
    $user = actingAsAuthenticatedUser();
    $originalPoints = $this->customer->available_points;

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'ADJUST',
        'points' => 50,
        'description' => '補償點數',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.type', 'ADJUST')
        ->assertJsonPath('data.points', 50)
        ->assertJsonPath('data.balance', $originalPoints + 50);
});

// 測試成功調整點數（ADJUST 類型 - 負數）
it('可以用 ADJUST 類型扣除點數', function () {
    $user = actingAsAuthenticatedUser();
    $originalPoints = $this->customer->available_points;

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'ADJUST',
        'points' => -50,
        'description' => '修正點數',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.type', 'ADJUST')
        ->assertJsonPath('data.points', -50)
        ->assertJsonPath('data.balance', $originalPoints - 50);
});

// 測試獎勵點數（BONUS 類型）
it('可以用 BONUS 類型發放獎勵點數', function () {
    $user = actingAsAuthenticatedUser();
    $originalPoints = $this->customer->available_points;

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'BONUS',
        'points' => 200,
        'description' => '生日禮物',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.type', 'BONUS')
        ->assertJsonPath('data.points', 200) // BONUS 類型會轉為正數
        ->assertJsonPath('data.balance', $originalPoints + 200);
});

// 測試過期扣點（EXPIRE 類型）
it('可以用 EXPIRE 類型扣除過期點數', function () {
    $user = actingAsAuthenticatedUser();
    $originalPoints = $this->customer->available_points;

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'EXPIRE',
        'points' => 100,
        'description' => '點數過期',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.type', 'EXPIRE')
        ->assertJsonPath('data.points', -100) // EXPIRE 類型會轉為負數
        ->assertJsonPath('data.balance', $originalPoints - 100);
});

// 測試點數不足無法扣除
it('會員點數不足時無法扣除', function () {
    $user = actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'REDEEM',
        'points' => 1000, // 超過會員可用點數 500
        'description' => '兌換商品',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

// 測試未認證調整點數
it('未認證使用者調整點數會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'EARN',
        'points' => 100,
    ]);

    $response->assertStatus(401);
});

// 測試調整不存在會員的點數
it('調整不存在會員的點數會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('customers/99999/points'), [
        'type' => 'EARN',
        'points' => 100,
    ]);

    $response->assertStatus(404);
});

// ==================== 驗證規則測試 ====================

// 測試異動類型為必填
it('異動類型為必填欄位', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'points' => 100,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

// 測試異動類型必須有效
it('異動類型必須是有效的值', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'INVALID_TYPE',
        'points' => 100,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

// 測試點數為必填
it('點數為必填欄位', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'EARN',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['points']);
});

// 測試點數必須為整數
it('點數必須為整數', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'EARN',
        'points' => 'abc',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['points']);
});

// 測試描述長度限制
it('描述不能超過 200 字元', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'EARN',
        'points' => 100,
        'description' => str_repeat('測試', 150),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['description']);
});

// 測試到期日必須是未來日期
it('到期日必須是未來日期', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'EARN',
        'points' => 100,
        'expire_date' => now()->subDay()->toDateString(),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['expire_date']);
});

// 測試可以設定到期日
it('可以設定點數到期日', function () {
    $user = actingAsAuthenticatedUser();
    $expireDate = now()->addYear()->toDateString();

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'EARN',
        'points' => 100,
        'description' => '有效期一年的點數',
        'expire_date' => $expireDate,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true);

    $log = \App\Models\PointsLog::where('customer_id', $this->customer->id)
        ->whereNotNull('expire_date')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->expire_date->toDateString())->toBe($expireDate);
});

// ==================== 各類型點數測試 ====================

// 測試 REFUND 類型會扣除點數
it('REFUND 類型會扣除點數', function () {
    $user = actingAsAuthenticatedUser();
    $originalPoints = $this->customer->available_points;

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'REFUND',
        'points' => 50,
        'description' => '退貨扣點',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.type', 'REFUND')
        ->assertJsonPath('data.points', -50); // REFUND 類型會轉為負數
});

// ==================== 總點數更新測試 ====================

// 測試增加點數時會更新總點數
it('增加點數時會更新總點數', function () {
    $user = actingAsAuthenticatedUser();
    $originalTotalPoints = $this->customer->total_points;

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'EARN',
        'points' => 100,
    ]);

    $response->assertStatus(201);

    $this->customer->refresh();
    $this->assertEquals($originalTotalPoints + 100, $this->customer->total_points);
});

// 測試扣除點數時不會更新總點數
it('扣除點數時不會增加總點數', function () {
    $user = actingAsAuthenticatedUser();
    $originalTotalPoints = $this->customer->total_points;

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'REDEEM',
        'points' => 50,
    ]);

    $response->assertStatus(201);

    $this->customer->refresh();
    $this->assertEquals($originalTotalPoints, $this->customer->total_points);
});

// ==================== 預設值測試 ====================

// 測試不提供描述時使用預設值
it('不提供描述時使用預設描述', function () {
    $user = actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'EARN',
        'points' => 100,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.description', '手動調整點數');
});

// 測試點數紀錄的 reference_type 為 MANUAL
it('手動調整點數的 reference_type 為 MANUAL', function () {
    $user = actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl("customers/{$this->customer->id}/points"), [
        'type' => 'EARN',
        'points' => 100,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.reference_type', 'MANUAL');
});
