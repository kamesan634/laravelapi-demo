<?php

/**
 * 退貨 API 測試
 *
 * 測試退貨單的 CRUD 操作及審核流程
 */

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Refund;
use App\Models\RefundItem;
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
        'available_points' => 100,
        'total_points' => 100,
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
 * 建立測試用已完成訂單（含訂單明細）
 */
function createCompletedOrderWithItems($store, $user, $customer, $product): array
{
    $order = Order::create([
        'order_no' => 'SO'.date('Ymd').str_pad(Order::count() + 1, 4, '0', STR_PAD_LEFT),
        'store_id' => $store->id,
        'cashier_id' => $user->id,
        'customer_id' => $customer->id,
        'order_date' => now(),
        'subtotal' => 200,
        'discount_amount' => 0,
        'tax_amount' => 10,
        'total_amount' => 210,
        'status' => 'COMPLETED',
    ]);

    $orderItem = OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'sku' => $product->sku,
        'quantity' => 2,
        'unit_price' => 100,
        'original_price' => 100,
        'discount_amount' => 0,
        'tax_amount' => 5,
        'subtotal' => 200,
        'cost_price' => 50,
    ]);

    return ['order' => $order, 'orderItem' => $orderItem];
}

/**
 * 建立測試用退貨單
 */
function createRefund($order, $store, $user, $status = 'COMPLETED'): Refund
{
    return Refund::create([
        'refund_no' => 'RF'.date('Ymd').str_pad(Refund::count() + 1, 4, '0', STR_PAD_LEFT),
        'order_id' => $order->id,
        'store_id' => $store->id,
        'cashier_id' => $user->id,
        'refund_date' => now(),
        'refund_type' => 'REFUND',
        'reason_code' => 'DEFECT',
        'reason_note' => '商品瑕疵',
        'refund_amount' => 100,
        'refund_method' => 'CASH',
        'points_deducted' => 0,
        'status' => $status,
    ]);
}

// ==================== 列表測試 ====================

// 測試取得退貨單列表
it('已認證使用者可以取得退貨單列表', function () {
    $user = actingAsAuthenticatedUser();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    createRefund($data['order'], $this->store, $user);

    $response = $this->getJson(apiUrl('refunds'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'refund_no',
                    'order_id',
                    'store_id',
                    'cashier_id',
                    'refund_date',
                    'refund_type',
                    'reason_code',
                    'refund_amount',
                    'refund_method',
                    'status',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

// 測試未認證取得退貨單列表
it('未認證使用者取得退貨單列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('refunds'));

    $response->assertStatus(401);
});

// 測試依退貨單編號搜尋
it('可以透過退貨單編號搜尋退貨單', function () {
    $user = actingAsAuthenticatedUser();

    $data1 = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    $refund1 = createRefund($data1['order'], $this->store, $user);
    $refund1->update(['refund_no' => 'RF202401010001']);

    $data2 = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    $refund2 = createRefund($data2['order'], $this->store, $user);
    $refund2->update(['refund_no' => 'RF202401020001']);

    $response = $this->getJson(apiUrl('refunds?keyword=RF202401010001'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試依狀態篩選
it('可以透過狀態篩選退貨單', function () {
    $user = actingAsAuthenticatedUser();

    $data1 = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    createRefund($data1['order'], $this->store, $user, 'COMPLETED');

    $data2 = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    createRefund($data2['order'], $this->store, $user, 'CANCELLED');

    $response = $this->getJson(apiUrl('refunds?status=COMPLETED'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試日期範圍篩選
it('可以透過日期範圍篩選退貨單', function () {
    $user = actingAsAuthenticatedUser();

    $data1 = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    $refund1 = createRefund($data1['order'], $this->store, $user);
    $refund1->update(['refund_date' => now()->subDays(5)]);

    $data2 = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    createRefund($data2['order'], $this->store, $user);

    $startDate = now()->subDays(2)->toDateString();
    $endDate = now()->addDay()->toDateString();

    $response = $this->getJson(apiUrl("refunds?start_date={$startDate}&end_date={$endDate}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試分頁功能
it('退貨單列表支援分頁', function () {
    $user = actingAsAuthenticatedUser();

    // 建立多筆退貨單
    for ($i = 1; $i <= 20; $i++) {
        $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
        createRefund($data['order'], $this->store, $user);
    }

    $response = $this->getJson(apiUrl('refunds?per_page=5&page=1'));

    $response->assertStatus(200);
    $this->assertEquals(5, count($response->json('data')));
    $this->assertEquals(20, $response->json('meta.total'));
    $this->assertEquals(4, $response->json('meta.last_page'));
});

// ==================== 查看單一退貨單測試 ====================

// 測試取得單一退貨單
it('已認證使用者可以取得單一退貨單詳情', function () {
    $user = actingAsAuthenticatedUser();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    $refund = createRefund($data['order'], $this->store, $user);

    // 建立退貨明細
    RefundItem::create([
        'refund_id' => $refund->id,
        'order_item_id' => $data['orderItem']->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
        'unit_price' => 100,
        'refund_amount' => 100,
    ]);

    $response = $this->getJson(apiUrl("refunds/{$refund->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $refund->id)
        ->assertJsonPath('data.refund_no', $refund->refund_no)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'refund_no',
                'order_id',
                'store_id',
                'refund_date',
                'refund_type',
                'reason_code',
                'refund_amount',
                'status',
                'order',
                'items',
                'store',
                'cashier',
            ],
        ]);
});

// 測試取得不存在的退貨單
it('取得不存在的退貨單會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('refunds/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一退貨單
it('未認證使用者取得單一退貨單會回傳 401 錯誤', function () {
    $user = User::factory()->create();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    $refund = createRefund($data['order'], $this->store, $user);

    $response = $this->getJson(apiUrl("refunds/{$refund->id}"));

    $response->assertStatus(401);
});

// ==================== 新增退貨單測試 ====================

// 測試成功新增退貨單
it('已認證使用者可以新增退貨單', function () {
    $user = actingAsAuthenticatedUser();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);

    $refundData = [
        'order_id' => $data['order']->id,
        'store_id' => $this->store->id,
        'refund_type' => 'REFUND',
        'reason_code' => 'DEFECT',
        'reason_note' => '商品有瑕疵',
        'refund_method' => 'CASH',
        'items' => [
            [
                'order_item_id' => $data['orderItem']->id,
                'quantity' => 1,
                'refund_amount' => 100,
                'reason' => '有刮痕',
            ],
        ],
    ];

    $response = $this->postJson(apiUrl('refunds'), $refundData);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'refund_no',
                'order_id',
                'store_id',
                'refund_type',
                'reason_code',
                'refund_amount',
                'status',
            ],
        ]);

    $this->assertDatabaseHas('refunds', [
        'order_id' => $data['order']->id,
        'refund_type' => 'REFUND',
        'reason_code' => 'DEFECT',
    ]);
});

// 測試未認證新增退貨單
it('未認證使用者新增退貨單會回傳 401 錯誤', function () {
    $user = User::factory()->create();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);

    $response = $this->postJson(apiUrl('refunds'), [
        'order_id' => $data['order']->id,
        'store_id' => $this->store->id,
        'refund_type' => 'REFUND',
        'reason_code' => 'DEFECT',
        'refund_method' => 'CASH',
        'items' => [
            [
                'order_item_id' => $data['orderItem']->id,
                'quantity' => 1,
                'refund_amount' => 100,
            ],
        ],
    ]);

    $response->assertStatus(401);
});

// 測試未完成訂單無法退貨
it('未完成狀態的訂單無法進行退貨', function () {
    $user = actingAsAuthenticatedUser();

    // 建立未完成的訂單
    $order = Order::create([
        'order_no' => 'SO202401010001',
        'store_id' => $this->store->id,
        'cashier_id' => $user->id,
        'order_date' => now(),
        'subtotal' => 200,
        'discount_amount' => 0,
        'tax_amount' => 10,
        'total_amount' => 210,
        'status' => 'VOIDED',
    ]);

    $orderItem = OrderItem::create([
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

    $response = $this->postJson(apiUrl('refunds'), [
        'order_id' => $order->id,
        'store_id' => $this->store->id,
        'refund_type' => 'REFUND',
        'reason_code' => 'DEFECT',
        'refund_method' => 'CASH',
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 1,
                'refund_amount' => 100,
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

// 測試退貨數量超過可退數量
it('退貨數量超過可退數量會回傳 422 錯誤', function () {
    $user = actingAsAuthenticatedUser();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);

    $response = $this->postJson(apiUrl('refunds'), [
        'order_id' => $data['order']->id,
        'store_id' => $this->store->id,
        'refund_type' => 'REFUND',
        'reason_code' => 'DEFECT',
        'refund_method' => 'CASH',
        'items' => [
            [
                'order_item_id' => $data['orderItem']->id,
                'quantity' => 10, // 超過原訂單數量 2
                'refund_amount' => 1000,
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

// ==================== 驗證規則測試 ====================

// 測試訂單 ID 為必填
it('訂單 ID 為必填欄位', function () {
    $user = actingAsAuthenticatedUser();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);

    $response = $this->postJson(apiUrl('refunds'), [
        'store_id' => $this->store->id,
        'refund_type' => 'REFUND',
        'reason_code' => 'DEFECT',
        'refund_method' => 'CASH',
        'items' => [
            [
                'order_item_id' => $data['orderItem']->id,
                'quantity' => 1,
                'refund_amount' => 100,
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['order_id']);
});

// 測試門市 ID 為必填
it('門市 ID 為必填欄位', function () {
    $user = actingAsAuthenticatedUser();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);

    $response = $this->postJson(apiUrl('refunds'), [
        'order_id' => $data['order']->id,
        'refund_type' => 'REFUND',
        'reason_code' => 'DEFECT',
        'refund_method' => 'CASH',
        'items' => [
            [
                'order_item_id' => $data['orderItem']->id,
                'quantity' => 1,
                'refund_amount' => 100,
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['store_id']);
});

// 測試退貨類型為必填
it('退貨類型為必填欄位', function () {
    $user = actingAsAuthenticatedUser();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);

    $response = $this->postJson(apiUrl('refunds'), [
        'order_id' => $data['order']->id,
        'store_id' => $this->store->id,
        'reason_code' => 'DEFECT',
        'refund_method' => 'CASH',
        'items' => [
            [
                'order_item_id' => $data['orderItem']->id,
                'quantity' => 1,
                'refund_amount' => 100,
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['refund_type']);
});

// 測試退貨類型必須有效
it('退貨類型必須是有效的值', function () {
    $user = actingAsAuthenticatedUser();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);

    $response = $this->postJson(apiUrl('refunds'), [
        'order_id' => $data['order']->id,
        'store_id' => $this->store->id,
        'refund_type' => 'INVALID_TYPE',
        'reason_code' => 'DEFECT',
        'refund_method' => 'CASH',
        'items' => [
            [
                'order_item_id' => $data['orderItem']->id,
                'quantity' => 1,
                'refund_amount' => 100,
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['refund_type']);
});

// 測試退貨明細為必填
it('退貨明細為必填欄位', function () {
    $user = actingAsAuthenticatedUser();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);

    $response = $this->postJson(apiUrl('refunds'), [
        'order_id' => $data['order']->id,
        'store_id' => $this->store->id,
        'refund_type' => 'REFUND',
        'reason_code' => 'DEFECT',
        'refund_method' => 'CASH',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

// ==================== 更新退貨單測試 ====================

// 測試更新已完成退貨單的備註
it('可以更新已完成退貨單的備註', function () {
    $user = actingAsAuthenticatedUser();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    $refund = createRefund($data['order'], $this->store, $user, 'COMPLETED');

    $response = $this->putJson(apiUrl("refunds/{$refund->id}"), [
        'reason_note' => '更新的備註內容',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('refunds', [
        'id' => $refund->id,
        'reason_note' => '更新的備註內容',
    ]);
});

// 測試更新非已完成狀態的退貨單會失敗
it('只能更新已完成狀態的退貨單', function () {
    $user = actingAsAuthenticatedUser();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    $refund = createRefund($data['order'], $this->store, $user, 'CANCELLED');

    $response = $this->putJson(apiUrl("refunds/{$refund->id}"), [
        'reason_note' => '嘗試更新的備註',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

// ==================== 刪除退貨單測試 ====================

// 測試刪除已取消的退貨單
it('可以刪除已取消的退貨單', function () {
    $user = actingAsAuthenticatedUser();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    $refund = createRefund($data['order'], $this->store, $user, 'CANCELLED');

    $response = $this->deleteJson(apiUrl("refunds/{$refund->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('refunds', [
        'id' => $refund->id,
    ]);
});

// 測試無法刪除非取消狀態的退貨單
it('只能刪除已取消狀態的退貨單', function () {
    $user = actingAsAuthenticatedUser();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    $refund = createRefund($data['order'], $this->store, $user, 'COMPLETED');

    $response = $this->deleteJson(apiUrl("refunds/{$refund->id}"));

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

// 測試未認證刪除退貨單
it('未認證使用者刪除退貨單會回傳 401 錯誤', function () {
    $user = User::factory()->create();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    $refund = createRefund($data['order'], $this->store, $user, 'CANCELLED');

    $response = $this->deleteJson(apiUrl("refunds/{$refund->id}"));

    $response->assertStatus(401);
});

// ==================== 退貨明細測試 ====================

// 測試取得退貨明細
it('已認證使用者可以取得退貨明細', function () {
    $user = actingAsAuthenticatedUser();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    $refund = createRefund($data['order'], $this->store, $user);

    RefundItem::create([
        'refund_id' => $refund->id,
        'order_item_id' => $data['orderItem']->id,
        'product_id' => $this->product->id,
        'quantity' => 1,
        'unit_price' => 100,
        'refund_amount' => 100,
    ]);

    $response = $this->getJson(apiUrl("refunds/{$refund->id}/items"));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'refund_id',
                    'order_item_id',
                    'product_id',
                    'quantity',
                    'unit_price',
                    'refund_amount',
                ],
            ],
        ]);
});

// 測試未認證取得退貨明細
it('未認證使用者取得退貨明細會回傳 401 錯誤', function () {
    $user = User::factory()->create();
    $data = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    $refund = createRefund($data['order'], $this->store, $user);

    $response = $this->getJson(apiUrl("refunds/{$refund->id}/items"));

    $response->assertStatus(401);
});

// ==================== 排序測試 ====================

// 測試排序功能
it('可以根據指定欄位排序退貨單', function () {
    $user = actingAsAuthenticatedUser();

    $data1 = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    $refund1 = createRefund($data1['order'], $this->store, $user);
    $refund1->update(['refund_amount' => 100]);

    $data2 = createCompletedOrderWithItems($this->store, $user, $this->customer, $this->product);
    $refund2 = createRefund($data2['order'], $this->store, $user);
    $refund2->update(['refund_amount' => 500]);

    $response = $this->getJson(apiUrl('refunds?sort_by=refund_amount&sort_order=desc'));

    $response->assertStatus(200);
    $refunds = $response->json('data');
    $this->assertEquals('500.00', $refunds[0]['refund_amount']);
});
