<?php

/**
 * 訂單 API 測試
 *
 * 測試訂單的列表和查看功能（訂單通常不支援直接刪除，且新增邏輯複雜）
 */

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Order;
use App\Models\OrderItem;
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

// ==================== 列表測試 ====================

// 測試取得訂單列表
it('已認證使用者可以取得訂單列表', function () {
    $user = actingAsAuthenticatedUser();

    // 建立測試訂單
    $order = Order::create([
        'order_no' => 'SO202401010001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'customer_id' => $this->customer->id,
        'order_date' => now(),
        'subtotal' => 200,
        'discount_amount' => 0,
        'tax_amount' => 10,
        'total_amount' => 210,
        'status' => 'COMPLETED',
    ]);

    // 建立訂單明細
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'product_name' => $this->product->name,
        'sku' => $this->product->sku,
        'quantity' => 2,
        'unit_price' => 100,
        'original_price' => 100,
        'discount_amount' => 0,
        'tax_amount' => 5,
        'subtotal' => 200,
        'cost_price' => 50,
    ]);

    $response = $this->getJson(apiUrl('orders'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'order_no',
                    'store_id',
                    'customer_id',
                    'order_date',
                    'subtotal',
                    'total_amount',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

// 測試未認證取得訂單列表
it('未認證使用者取得訂單列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('orders'));

    $response->assertStatus(401);
});

// 測試搜尋訂單列表 - 依訂單編號
it('可以透過訂單編號搜尋訂單', function () {
    $user = actingAsAuthenticatedUser();

    Order::create([
        'order_no' => 'SO202401010001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'order_date' => now(),
        'subtotal' => 100,
        'discount_amount' => 0,
        'tax_amount' => 5,
        'total_amount' => 105,
        'status' => 'COMPLETED',
    ]);
    Order::create([
        'order_no' => 'SO202401020001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'order_date' => now(),
        'subtotal' => 200,
        'discount_amount' => 0,
        'tax_amount' => 10,
        'total_amount' => 210,
        'status' => 'COMPLETED',
    ]);

    $response = $this->getJson(apiUrl('orders?keyword=SO202401010001'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試門市篩選
it('可以透過門市篩選訂單', function () {
    $user = actingAsAuthenticatedUser();

    $store2 = Store::create(['code' => 'S002', 'name' => '門市二', 'status' => 'ACTIVE']);

    Order::create([
        'order_no' => 'SO202401010001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'order_date' => now(),
        'subtotal' => 100,
        'discount_amount' => 0,
        'tax_amount' => 5,
        'total_amount' => 105,
        'status' => 'COMPLETED',
    ]);
    Order::create([
        'order_no' => 'SO202401010002',
        'store_id' => $store2->id,
        'cashier_id' => $user->id,
        'order_date' => now(),
        'subtotal' => 200,
        'discount_amount' => 0,
        'tax_amount' => 10,
        'total_amount' => 210,
        'status' => 'COMPLETED',
    ]);

    $response = $this->getJson(apiUrl("orders?store_id={$this->store->id}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試會員篩選
it('可以透過會員篩選訂單', function () {
    $user = actingAsAuthenticatedUser();

    $customer2 = Customer::create([
        'member_no' => 'M002',
        'name' => '會員二',
        'phone' => '0923456789',
        'level_id' => $this->customerLevel->id,
        'join_date' => now()->toDateString(),
        'status' => 'ACTIVE',
    ]);

    Order::create([
        'order_no' => 'SO202401010001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'customer_id' => $this->customer->id,
        'order_date' => now(),
        'subtotal' => 100,
        'discount_amount' => 0,
        'tax_amount' => 5,
        'total_amount' => 105,
        'status' => 'COMPLETED',
    ]);
    Order::create([
        'order_no' => 'SO202401010002',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'customer_id' => $customer2->id,
        'order_date' => now(),
        'subtotal' => 200,
        'discount_amount' => 0,
        'tax_amount' => 10,
        'total_amount' => 210,
        'status' => 'COMPLETED',
    ]);

    $response = $this->getJson(apiUrl("orders?customer_id={$this->customer->id}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試狀態篩選
it('可以透過狀態篩選訂單', function () {
    $user = actingAsAuthenticatedUser();

    Order::create([
        'order_no' => 'SO202401010001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'order_date' => now(),
        'subtotal' => 100,
        'discount_amount' => 0,
        'tax_amount' => 5,
        'total_amount' => 105,
        'status' => 'COMPLETED',
    ]);
    Order::create([
        'order_no' => 'SO202401010002',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'order_date' => now(),
        'subtotal' => 200,
        'discount_amount' => 0,
        'tax_amount' => 10,
        'total_amount' => 210,
        'status' => 'VOIDED',
    ]);

    $response = $this->getJson(apiUrl('orders?status=COMPLETED'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試日期範圍篩選
it('可以透過日期範圍篩選訂單', function () {
    $user = actingAsAuthenticatedUser();

    Order::create([
        'order_no' => 'SO202401010001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'order_date' => now()->subDays(5),
        'subtotal' => 100,
        'discount_amount' => 0,
        'tax_amount' => 5,
        'total_amount' => 105,
        'status' => 'COMPLETED',
    ]);
    Order::create([
        'order_no' => 'SO202401010002',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'order_date' => now(),
        'subtotal' => 200,
        'discount_amount' => 0,
        'tax_amount' => 10,
        'total_amount' => 210,
        'status' => 'COMPLETED',
    ]);

    $startDate = now()->subDays(2)->toDateString();
    $endDate = now()->addDay()->toDateString();

    $response = $this->getJson(apiUrl("orders?start_date={$startDate}&end_date={$endDate}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// ==================== 查看單一訂單測試 ====================

// 測試取得單一訂單
it('已認證使用者可以取得單一訂單詳情', function () {
    $user = actingAsAuthenticatedUser();

    $order = Order::create([
        'order_no' => 'SO202401010001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'customer_id' => $this->customer->id,
        'order_date' => now(),
        'subtotal' => 200,
        'discount_amount' => 0,
        'tax_amount' => 10,
        'total_amount' => 210,
        'status' => 'COMPLETED',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'product_name' => $this->product->name,
        'sku' => $this->product->sku,
        'quantity' => 2,
        'unit_price' => 100,
        'original_price' => 100,
        'discount_amount' => 0,
        'tax_amount' => 5,
        'subtotal' => 200,
        'cost_price' => 50,
    ]);

    $response = $this->getJson(apiUrl("orders/{$order->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $order->id)
        ->assertJsonPath('data.order_no', 'SO202401010001')
        ->assertJsonPath('data.total_amount', '210.00')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'order_no',
                'store_id',
                'customer_id',
                'order_date',
                'subtotal',
                'discount_amount',
                'tax_amount',
                'total_amount',
                'points_earned',
                'points_used',
                'status',
                'store',
                'customer',
                'items',
                'cashier',
            ],
        ]);
});

// 測試取得不存在的訂單
it('取得不存在的訂單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('orders/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一訂單
it('未認證使用者取得單一訂單會回傳 401 錯誤', function () {
    $user = User::factory()->create();

    $order = Order::create([
        'order_no' => 'SO202401010001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'order_date' => now(),
        'subtotal' => 100,
        'discount_amount' => 0,
        'tax_amount' => 5,
        'total_amount' => 105,
        'status' => 'COMPLETED',
    ]);

    $response = $this->getJson(apiUrl("orders/{$order->id}"));

    $response->assertStatus(401);
});

// ==================== 訂單明細測試 ====================

// 測試取得訂單明細
it('已認證使用者可以取得訂單明細', function () {
    $user = actingAsAuthenticatedUser();

    $order = Order::create([
        'order_no' => 'SO202401010001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'order_date' => now(),
        'subtotal' => 300,
        'discount_amount' => 0,
        'tax_amount' => 15,
        'total_amount' => 315,
        'status' => 'COMPLETED',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $this->product->id,
        'product_name' => $this->product->name,
        'sku' => $this->product->sku,
        'quantity' => 2,
        'unit_price' => 100,
        'original_price' => 100,
        'discount_amount' => 0,
        'tax_amount' => 5,
        'subtotal' => 200,
        'cost_price' => 50,
    ]);

    $product2 = Product::create([
        'sku' => 'SKU002',
        'name' => '商品二',
        'category_id' => $this->category->id,
        'unit' => 'PCS',
        'cost_price' => 30,
        'selling_price' => 50,
        'status' => 'ACTIVE',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'product_name' => $product2->name,
        'sku' => $product2->sku,
        'quantity' => 2,
        'unit_price' => 50,
        'original_price' => 50,
        'discount_amount' => 0,
        'tax_amount' => 5,
        'subtotal' => 100,
        'cost_price' => 30,
    ]);

    $response = $this->getJson(apiUrl("orders/{$order->id}/items"));

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'order_id',
                    'product_id',
                    'product_name',
                    'sku',
                    'quantity',
                    'unit_price',
                    'subtotal',
                ],
            ],
        ]);
});

// 測試未認證取得訂單明細
it('未認證使用者取得訂單明細會回傳 401 錯誤', function () {
    $user = User::factory()->create();

    $order = Order::create([
        'order_no' => 'SO202401010001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'order_date' => now(),
        'subtotal' => 100,
        'discount_amount' => 0,
        'tax_amount' => 5,
        'total_amount' => 105,
        'status' => 'COMPLETED',
    ]);

    $response = $this->getJson(apiUrl("orders/{$order->id}/items"));

    $response->assertStatus(401);
});

// ==================== 訂單統計測試 ====================

// 測試訂單列表包含正確的關聯資料
it('訂單列表包含門市、會員和收銀員資訊', function () {
    $user = actingAsAuthenticatedUser();

    Order::create([
        'order_no' => 'SO202401010001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'customer_id' => $this->customer->id,
        'order_date' => now(),
        'subtotal' => 100,
        'discount_amount' => 0,
        'tax_amount' => 5,
        'total_amount' => 105,
        'status' => 'COMPLETED',
    ]);

    $response = $this->getJson(apiUrl('orders'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'store',
                    'customer',
                    'cashier',
                ],
            ],
        ]);
});

// 測試無會員訂單
it('可以查詢沒有會員的訂單', function () {
    $user = actingAsAuthenticatedUser();

    Order::create([
        'order_no' => 'SO202401010001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'customer_id' => null, // 無會員
        'order_date' => now(),
        'subtotal' => 100,
        'discount_amount' => 0,
        'tax_amount' => 5,
        'total_amount' => 105,
        'status' => 'COMPLETED',
    ]);

    $response = $this->getJson(apiUrl('orders'));

    $response->assertStatus(200);
    $this->assertNull($response->json('data.0.customer_id'));
});

// 測試訂單點數使用記錄
it('訂單詳情包含點數使用資訊', function () {
    $user = actingAsAuthenticatedUser();

    $order = Order::create([
        'order_no' => 'SO202401010001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'customer_id' => $this->customer->id,
        'order_date' => now(),
        'subtotal' => 200,
        'discount_amount' => 0,
        'tax_amount' => 10,
        'total_amount' => 160,
        'points_earned' => 16,
        'points_used' => 50,
        'points_amount' => 50,
        'status' => 'COMPLETED',
    ]);

    $response = $this->getJson(apiUrl("orders/{$order->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.points_earned', 16)
        ->assertJsonPath('data.points_used', 50)
        ->assertJsonPath('data.points_amount', '50.00');
});

// 測試排序功能
it('可以根據指定欄位排序訂單', function () {
    $user = actingAsAuthenticatedUser();

    Order::create([
        'order_no' => 'SO202401010001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'order_date' => now()->subDays(2),
        'subtotal' => 100,
        'discount_amount' => 0,
        'tax_amount' => 5,
        'total_amount' => 105,
        'status' => 'COMPLETED',
    ]);
    Order::create([
        'order_no' => 'SO202401010002',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'order_date' => now(),
        'subtotal' => 200,
        'discount_amount' => 0,
        'tax_amount' => 10,
        'total_amount' => 210,
        'status' => 'COMPLETED',
    ]);

    // 依總金額降序排序
    $response = $this->getJson(apiUrl('orders?sort_by=total_amount&sort_order=desc'));

    $response->assertStatus(200);
    $orders = $response->json('data');
    $this->assertEquals('SO202401010002', $orders[0]['order_no']);
});

// 測試分頁功能
it('訂單列表支援分頁', function () {
    $user = actingAsAuthenticatedUser();

    // 建立多筆訂單
    for ($i = 1; $i <= 20; $i++) {
        Order::create([
            'order_no' => sprintf('SO20240101%04d', $i),
            'store_id' => $this->store->id,
            'cashier_id' => $user->id,
            'order_date' => now(),
            'subtotal' => 100 * $i,
            'discount_amount' => 0,
            'tax_amount' => 5 * $i,
            'total_amount' => 105 * $i,
            'status' => 'COMPLETED',
        ]);
    }

    // 取得第一頁，每頁 5 筆
    $response = $this->getJson(apiUrl('orders?per_page=5&page=1'));

    $response->assertStatus(200);
    $this->assertEquals(5, count($response->json('data')));
    $this->assertEquals(20, $response->json('meta.total'));
    $this->assertEquals(4, $response->json('meta.last_page'));
});
