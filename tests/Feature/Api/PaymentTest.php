<?php

/**
 * 付款 API 測試
 *
 * 測試訂單付款的新增與查詢功能
 */

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
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
        'status' => 'ACTIVE',
    ]);

    // 建立預設分類
    $this->category = Category::create([
        'code' => 'CAT001',
        'name' => '測試分類',
        'status' => 'ACTIVE',
    ]);

    // 建立預設商品
    $this->product = Product::create([
        'sku' => 'SKU001',
        'name' => '測試商品',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'cost_price' => 50,
        'selling_price' => 100,
        'status' => 'ACTIVE',
    ]);
});

/**
 * 建立測試用已完成訂單
 */
function createCompletedOrder($store, $user, $customer = null, $totalAmount = 500): Order
{
    $order = Order::create([
        'order_no' => 'SO'.date('Ymd').str_pad(Order::count() + 1, 4, '0', STR_PAD_LEFT),
        'store_id' => $store->id,
        'cashier_id' => $user->id,
        'customer_id' => $customer?->id,
        'order_date' => now(),
        'subtotal' => $totalAmount,
        'discount_amount' => 0,
        'tax_amount' => $totalAmount * 0.05,
        'total_amount' => $totalAmount,
        'status' => 'COMPLETED',
    ]);

    return $order;
}

// ==================== 查詢付款紀錄測試 ====================

// 測試查詢訂單付款紀錄
it('已認證使用者可以查詢訂單付款紀錄', function () {
    $user = actingAsAuthenticatedUser();
    $order = createCompletedOrder($this->store, $user, $this->customer, 500);

    // 建立付款紀錄
    Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'CASH',
        'amount' => 300,
        'received_amount' => 300,
        'change_amount' => 0,
        'status' => 'SUCCESS',
    ]);

    Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'CREDIT_CARD',
        'amount' => 200,
        'received_amount' => 200,
        'change_amount' => 0,
        'card_last_four' => '1234',
        'auth_code' => 'AUTH123',
        'status' => 'SUCCESS',
    ]);

    $response = $this->getJson(apiUrl("orders/{$order->id}/payments"));

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'order_id',
                    'payment_method',
                    'amount',
                    'received_amount',
                    'change_amount',
                    'status',
                ],
            ],
        ]);
});

// 測試未認證查詢付款紀錄
it('未認證使用者查詢付款紀錄會回傳 401 錯誤', function () {
    $user = User::factory()->create();
    $order = createCompletedOrder($this->store, $user, $this->customer, 500);

    $response = $this->getJson(apiUrl("orders/{$order->id}/payments"));

    $response->assertStatus(401);
});

// 測試查詢不存在訂單的付款紀錄
it('查詢不存在訂單的付款紀錄會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('orders/99999/payments'));

    $response->assertStatus(404);
});

// 測試查詢無付款紀錄的訂單
it('查詢無付款紀錄的訂單會回傳空陣列', function () {
    $user = actingAsAuthenticatedUser();
    $order = createCompletedOrder($this->store, $user, $this->customer, 500);

    $response = $this->getJson(apiUrl("orders/{$order->id}/payments"));

    $response->assertStatus(200)
        ->assertJsonCount(0, 'data');
});

// ==================== 新增付款測試 ====================

// 測試成功新增現金付款
it('已認證使用者可以新增現金付款', function () {
    $user = actingAsAuthenticatedUser();
    $order = createCompletedOrder($this->store, $user, $this->customer, 500);

    $paymentData = [
        'payment_method' => 'CASH',
        'amount' => 500,
        'received_amount' => 600,
        'change_amount' => 100,
    ];

    $response = $this->postJson(apiUrl("orders/{$order->id}/payments"), $paymentData);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.payment.payment_method', 'CASH')
        ->assertJsonPath('data.payment.amount', '500.00')
        ->assertJsonPath('data.payment.received_amount', '600.00')
        ->assertJsonPath('data.payment.change_amount', '100.00')
        ->assertJsonPath('data.payment.status', 'SUCCESS');

    $this->assertDatabaseHas('payments', [
        'order_id' => $order->id,
        'payment_method' => 'CASH',
        'amount' => 500,
    ]);
});

// 測試成功新增信用卡付款
it('已認證使用者可以新增信用卡付款', function () {
    $user = actingAsAuthenticatedUser();
    $order = createCompletedOrder($this->store, $user, $this->customer, 500);

    $paymentData = [
        'payment_method' => 'CREDIT_CARD',
        'amount' => 500,
        'card_last_four' => '1234',
        'auth_code' => 'AUTH001',
        'reference_no' => 'REF123456',
    ];

    $response = $this->postJson(apiUrl("orders/{$order->id}/payments"), $paymentData);

    $response->assertStatus(201)
        ->assertJsonPath('data.payment.payment_method', 'CREDIT_CARD')
        ->assertJsonPath('data.payment.card_last_four', '1234')
        ->assertJsonPath('data.payment.auth_code', 'AUTH001');

    $this->assertDatabaseHas('payments', [
        'order_id' => $order->id,
        'payment_method' => 'CREDIT_CARD',
        'card_last_four' => '1234',
    ]);
});

// 測試分次付款
it('可以進行分次付款', function () {
    $user = actingAsAuthenticatedUser();
    $order = createCompletedOrder($this->store, $user, $this->customer, 500);

    // 第一次付款 300
    $response1 = $this->postJson(apiUrl("orders/{$order->id}/payments"), [
        'payment_method' => 'CASH',
        'amount' => 300,
        'received_amount' => 300,
        'change_amount' => 0,
    ]);

    $response1->assertStatus(201);

    // 第二次付款 200
    $response2 = $this->postJson(apiUrl("orders/{$order->id}/payments"), [
        'payment_method' => 'CREDIT_CARD',
        'amount' => 200,
        'card_last_four' => '5678',
    ]);

    $response2->assertStatus(201);

    // 確認付款紀錄
    $this->assertEquals(2, Payment::where('order_id', $order->id)->count());
});

// 測試付款金額超過應付金額
it('付款金額超過應付金額會回傳 422 錯誤', function () {
    $user = actingAsAuthenticatedUser();
    $order = createCompletedOrder($this->store, $user, $this->customer, 500);

    // 先付款 300
    Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'CASH',
        'amount' => 300,
        'received_amount' => 300,
        'change_amount' => 0,
        'status' => 'SUCCESS',
    ]);

    // 嘗試付款 300（超過剩餘的 200）
    $response = $this->postJson(apiUrl("orders/{$order->id}/payments"), [
        'payment_method' => 'CASH',
        'amount' => 300,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

// 測試未完成訂單無法付款
it('未完成狀態的訂單無法進行付款', function () {
    $user = actingAsAuthenticatedUser();

    $order = Order::create([
        'order_no' => 'SO202401010001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'customer_id' => $this->customer->id,
        'order_date' => now(),
        'subtotal' => 500,
        'discount_amount' => 0,
        'tax_amount' => 25,
        'total_amount' => 500,
        'status' => 'VOIDED', // 非完成狀態
    ]);

    $response = $this->postJson(apiUrl("orders/{$order->id}/payments"), [
        'payment_method' => 'CASH',
        'amount' => 500,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

// 測試未認證新增付款
it('未認證使用者新增付款會回傳 401 錯誤', function () {
    $user = User::factory()->create();
    $order = createCompletedOrder($this->store, $user, $this->customer, 500);

    $response = $this->postJson(apiUrl("orders/{$order->id}/payments"), [
        'payment_method' => 'CASH',
        'amount' => 500,
    ]);

    $response->assertStatus(401);
});

// ==================== 驗證規則測試 ====================

// 測試付款方式為必填
it('付款方式為必填欄位', function () {
    $user = actingAsAuthenticatedUser();
    $order = createCompletedOrder($this->store, $user, $this->customer, 500);

    $response = $this->postJson(apiUrl("orders/{$order->id}/payments"), [
        'amount' => 500,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['payment_method']);
});

// 測試金額為必填
it('金額為必填欄位', function () {
    $user = actingAsAuthenticatedUser();
    $order = createCompletedOrder($this->store, $user, $this->customer, 500);

    $response = $this->postJson(apiUrl("orders/{$order->id}/payments"), [
        'payment_method' => 'CASH',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

// 測試金額必須大於零
it('金額必須大於零', function () {
    $user = actingAsAuthenticatedUser();
    $order = createCompletedOrder($this->store, $user, $this->customer, 500);

    $response = $this->postJson(apiUrl("orders/{$order->id}/payments"), [
        'payment_method' => 'CASH',
        'amount' => 0,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

// 測試卡號末四碼格式
it('卡號末四碼最多四個字元', function () {
    $user = actingAsAuthenticatedUser();
    $order = createCompletedOrder($this->store, $user, $this->customer, 500);

    $response = $this->postJson(apiUrl("orders/{$order->id}/payments"), [
        'payment_method' => 'CREDIT_CARD',
        'amount' => 500,
        'card_last_four' => '12345', // 超過 4 個字元
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['card_last_four']);
});

// ==================== 邊界條件測試 ====================

// 測試對不存在的訂單新增付款
it('對不存在的訂單新增付款會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('orders/99999/payments'), [
        'payment_method' => 'CASH',
        'amount' => 500,
    ]);

    $response->assertStatus(404);
});

// 測試付款紀錄按建立時間降序排列
it('付款紀錄按建立時間降序排列', function () {
    $user = actingAsAuthenticatedUser();
    $order = createCompletedOrder($this->store, $user, $this->customer, 1000);

    // 建立第一筆付款
    Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'CASH',
        'amount' => 300,
        'received_amount' => 300,
        'change_amount' => 0,
        'status' => 'SUCCESS',
        'created_at' => now()->subMinutes(10),
    ]);

    // 建立第二筆付款
    Payment::create([
        'order_id' => $order->id,
        'payment_method' => 'CREDIT_CARD',
        'amount' => 500,
        'status' => 'SUCCESS',
        'created_at' => now(),
    ]);

    $response = $this->getJson(apiUrl("orders/{$order->id}/payments"));

    $response->assertStatus(200);
    $payments = $response->json('data');

    // 最新的付款應該排在前面
    $this->assertEquals('CREDIT_CARD', $payments[0]['payment_method']);
    $this->assertEquals('CASH', $payments[1]['payment_method']);
});
