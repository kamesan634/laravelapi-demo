<?php

/**
 * 發票 API 測試
 *
 * 測試發票的查詢與作廢功能
 */

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Invoice;
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

/**
 * 建立測試用訂單（含發票）
 */
function createOrderWithInvoice($store, $user, $customer, $invoiceType = 'B2C', $voidFlag = false): array
{
    $order = Order::create([
        'order_no' => 'SO'.date('Ymd').str_pad(Order::count() + 1, 4, '0', STR_PAD_LEFT),
        'store_id' => $store->id,
        'cashier_id' => $user->id,
        'customer_id' => $customer?->id,
        'order_date' => now(),
        'subtotal' => 200,
        'discount_amount' => 0,
        'tax_amount' => 10,
        'total_amount' => 210,
        'status' => 'COMPLETED',
    ]);

    $invoice = Invoice::create([
        'invoice_no' => 'AB'.str_pad(Invoice::count() + 1, 8, '0', STR_PAD_LEFT),
        'order_id' => $order->id,
        'invoice_date' => now(),
        'invoice_type' => $invoiceType,
        'buyer_tax_id' => $invoiceType === 'B2B' ? '12345678' : null,
        'buyer_name' => $invoiceType === 'B2B' ? '測試公司' : null,
        'carrier_type' => $invoiceType === 'B2C' ? 'MOBILE' : null,
        'carrier_no' => $invoiceType === 'B2C' ? '/ABC1234' : null,
        'sales_amount' => 200,
        'tax_amount' => 10,
        'total_amount' => 210,
        'print_flag' => false,
        'void_flag' => $voidFlag,
        'void_date' => $voidFlag ? today() : null,
        'void_reason' => $voidFlag ? '作廢原因' : null,
    ]);

    return ['order' => $order, 'invoice' => $invoice];
}

// ==================== 列表測試 ====================

// 測試取得發票列表
it('已認證使用者可以取得發票列表', function () {
    $user = actingAsAuthenticatedUser();
    createOrderWithInvoice($this->store, $user, $this->customer);

    $response = $this->getJson(apiUrl('invoices'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'invoice_no',
                    'order_id',
                    'invoice_date',
                    'invoice_type',
                    'sales_amount',
                    'tax_amount',
                    'total_amount',
                    'void_flag',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

// 測試未認證取得發票列表
it('未認證使用者取得發票列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('invoices'));

    $response->assertStatus(401);
});

// 測試依發票號碼搜尋
it('可以透過發票號碼搜尋發票', function () {
    $user = actingAsAuthenticatedUser();

    $data1 = createOrderWithInvoice($this->store, $user, $this->customer);
    $data1['invoice']->update(['invoice_no' => 'AB12345678']);

    $data2 = createOrderWithInvoice($this->store, $user, $this->customer);
    $data2['invoice']->update(['invoice_no' => 'CD87654321']);

    $response = $this->getJson(apiUrl('invoices?keyword=AB12345678'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試依統一編號搜尋
it('可以透過統一編號搜尋發票', function () {
    $user = actingAsAuthenticatedUser();

    createOrderWithInvoice($this->store, $user, $this->customer, 'B2B');

    $response = $this->getJson(apiUrl('invoices?keyword=12345678'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試依買方名稱搜尋
it('可以透過買方名稱搜尋發票', function () {
    $user = actingAsAuthenticatedUser();

    createOrderWithInvoice($this->store, $user, $this->customer, 'B2B');

    $response = $this->getJson(apiUrl('invoices?keyword=測試公司'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試依發票類型篩選
it('可以透過發票類型篩選發票', function () {
    $user = actingAsAuthenticatedUser();

    createOrderWithInvoice($this->store, $user, $this->customer, 'B2C');
    createOrderWithInvoice($this->store, $user, $this->customer, 'B2B');

    $response = $this->getJson(apiUrl('invoices?invoice_type=B2C'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試依作廢狀態篩選
it('可以透過作廢狀態篩選發票', function () {
    $user = actingAsAuthenticatedUser();

    createOrderWithInvoice($this->store, $user, $this->customer, 'B2C', false);
    createOrderWithInvoice($this->store, $user, $this->customer, 'B2C', true);

    $response = $this->getJson(apiUrl('invoices?void_flag=true'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試篩選未作廢發票
it('可以篩選未作廢的發票', function () {
    $user = actingAsAuthenticatedUser();

    createOrderWithInvoice($this->store, $user, $this->customer, 'B2C', false);
    createOrderWithInvoice($this->store, $user, $this->customer, 'B2C', true);

    $response = $this->getJson(apiUrl('invoices?void_flag=false'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試日期範圍篩選
it('可以透過日期範圍篩選發票', function () {
    $user = actingAsAuthenticatedUser();

    $data1 = createOrderWithInvoice($this->store, $user, $this->customer);
    $data1['invoice']->update(['invoice_date' => now()->subDays(5)]);

    createOrderWithInvoice($this->store, $user, $this->customer);

    $startDate = now()->subDays(2)->toDateString();
    $endDate = now()->addDay()->toDateString();

    $response = $this->getJson(apiUrl("invoices?start_date={$startDate}&end_date={$endDate}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試分頁功能
it('發票列表支援分頁', function () {
    $user = actingAsAuthenticatedUser();

    for ($i = 1; $i <= 20; $i++) {
        createOrderWithInvoice($this->store, $user, $this->customer);
    }

    $response = $this->getJson(apiUrl('invoices?per_page=5&page=1'));

    $response->assertStatus(200);
    $this->assertEquals(5, count($response->json('data')));
    $this->assertEquals(20, $response->json('meta.total'));
    $this->assertEquals(4, $response->json('meta.last_page'));
});

// 測試排序功能
it('可以根據指定欄位排序發票', function () {
    $user = actingAsAuthenticatedUser();

    $data1 = createOrderWithInvoice($this->store, $user, $this->customer);
    $data1['invoice']->update(['total_amount' => 100]);

    $data2 = createOrderWithInvoice($this->store, $user, $this->customer);
    $data2['invoice']->update(['total_amount' => 500]);

    $response = $this->getJson(apiUrl('invoices?sort_by=total_amount&sort_order=desc'));

    $response->assertStatus(200);
    $invoices = $response->json('data');
    $this->assertEquals('500.00', $invoices[0]['total_amount']);
});

// ==================== 查看單一發票測試 ====================

// 測試取得單一發票
it('已認證使用者可以取得單一發票詳情', function () {
    $user = actingAsAuthenticatedUser();
    $data = createOrderWithInvoice($this->store, $user, $this->customer);

    $response = $this->getJson(apiUrl("invoices/{$data['invoice']->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $data['invoice']->id)
        ->assertJsonPath('data.invoice_no', $data['invoice']->invoice_no)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'invoice_no',
                'order_id',
                'invoice_date',
                'invoice_type',
                'buyer_tax_id',
                'buyer_name',
                'carrier_type',
                'carrier_no',
                'sales_amount',
                'tax_amount',
                'total_amount',
                'print_flag',
                'void_flag',
                'order',
            ],
        ]);
});

// 測試發票詳情包含訂單關聯資料
it('發票詳情包含訂單及相關資料', function () {
    $user = actingAsAuthenticatedUser();
    $data = createOrderWithInvoice($this->store, $user, $this->customer);

    // 建立訂單明細
    OrderItem::create([
        'order_id' => $data['order']->id,
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

    $response = $this->getJson(apiUrl("invoices/{$data['invoice']->id}"));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'order' => [
                    'id',
                    'order_no',
                    'customer',
                    'store',
                    'items',
                ],
            ],
        ]);
});

// 測試取得不存在的發票
it('取得不存在的發票會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('invoices/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一發票
it('未認證使用者取得單一發票會回傳 401 錯誤', function () {
    $user = User::factory()->create();
    $data = createOrderWithInvoice($this->store, $user, $this->customer);

    $response = $this->getJson(apiUrl("invoices/{$data['invoice']->id}"));

    $response->assertStatus(401);
});

// ==================== 作廢發票測試 ====================

// 測試成功作廢發票
it('已認證使用者可以作廢發票', function () {
    $user = actingAsAuthenticatedUser();
    $data = createOrderWithInvoice($this->store, $user, $this->customer);

    $response = $this->putJson(apiUrl("invoices/{$data['invoice']->id}/void"), [
        'void_reason' => '客戶要求作廢',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.void_flag', true);

    $this->assertDatabaseHas('invoices', [
        'id' => $data['invoice']->id,
        'void_flag' => true,
        'void_reason' => '客戶要求作廢',
    ]);
});

// 測試無法重複作廢發票
it('已作廢的發票無法再次作廢', function () {
    $user = actingAsAuthenticatedUser();
    $data = createOrderWithInvoice($this->store, $user, $this->customer, 'B2C', true);

    $response = $this->putJson(apiUrl("invoices/{$data['invoice']->id}/void"), [
        'void_reason' => '再次作廢',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

// 測試作廢發票需要原因
it('作廢發票必須提供原因', function () {
    $user = actingAsAuthenticatedUser();
    $data = createOrderWithInvoice($this->store, $user, $this->customer);

    $response = $this->putJson(apiUrl("invoices/{$data['invoice']->id}/void"), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['void_reason']);
});

// 測試作廢原因長度限制
it('作廢原因不能超過 255 字元', function () {
    $user = actingAsAuthenticatedUser();
    $data = createOrderWithInvoice($this->store, $user, $this->customer);

    $response = $this->putJson(apiUrl("invoices/{$data['invoice']->id}/void"), [
        'void_reason' => str_repeat('測試', 200), // 超過 255 字元
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['void_reason']);
});

// 測試未認證作廢發票
it('未認證使用者作廢發票會回傳 401 錯誤', function () {
    $user = User::factory()->create();
    $data = createOrderWithInvoice($this->store, $user, $this->customer);

    $response = $this->putJson(apiUrl("invoices/{$data['invoice']->id}/void"), [
        'void_reason' => '作廢原因',
    ]);

    $response->assertStatus(401);
});

// 測試作廢不存在的發票
it('作廢不存在的發票會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('invoices/99999/void'), [
        'void_reason' => '作廢原因',
    ]);

    $response->assertStatus(404);
});

// ==================== B2B 發票測試 ====================

// 測試 B2B 發票包含統一編號
it('B2B 發票詳情包含統一編號和買方名稱', function () {
    $user = actingAsAuthenticatedUser();
    $data = createOrderWithInvoice($this->store, $user, $this->customer, 'B2B');

    $response = $this->getJson(apiUrl("invoices/{$data['invoice']->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.invoice_type', 'B2B')
        ->assertJsonPath('data.buyer_tax_id', '12345678')
        ->assertJsonPath('data.buyer_name', '測試公司');
});

// ==================== B2C 發票測試 ====================

// 測試 B2C 發票包含載具資訊
it('B2C 發票詳情包含載具資訊', function () {
    $user = actingAsAuthenticatedUser();
    $data = createOrderWithInvoice($this->store, $user, $this->customer, 'B2C');

    $response = $this->getJson(apiUrl("invoices/{$data['invoice']->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.invoice_type', 'B2C')
        ->assertJsonPath('data.carrier_type', 'MOBILE')
        ->assertJsonPath('data.carrier_no', '/ABC1234');
});

// ==================== 發票列表包含關聯資料測試 ====================

// 測試發票列表包含訂單關聯
it('發票列表包含訂單資訊', function () {
    $user = actingAsAuthenticatedUser();
    createOrderWithInvoice($this->store, $user, $this->customer);

    $response = $this->getJson(apiUrl('invoices'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'order',
                ],
            ],
        ]);
});
