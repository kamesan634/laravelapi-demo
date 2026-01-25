<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptItem;
use App\Models\Supplier;
use App\Models\Warehouse;

/**
 * 採購收貨單 API 測試
 *
 * 測試採購收貨的 CRUD 操作及確認收貨流程
 */
describe('採購收貨單 API', function () {

    /**
     * 設置測試前置條件
     */
    beforeEach(function () {
        // 建立供應商
        $this->supplier = Supplier::create([
            'code' => 'SUP001',
            'name' => '測試供應商',
            'status' => 'ACTIVE',
        ]);

        // 建立倉庫
        $this->warehouse = Warehouse::create([
            'code' => 'WH001',
            'name' => '主倉庫',
            'type' => 'WAREHOUSE',
            'status' => 'ACTIVE',
        ]);

        // 建立分類
        $this->category = Category::create([
            'code' => 'CAT001',
            'name' => '測試分類',
            'status' => 'ACTIVE',
        ]);

        // 建立商品
        $this->product = Product::create([
            'sku' => 'PROD001',
            'name' => '測試商品',
            'category_id' => $this->category->id,
            'unit' => 'PCS',
            'selling_price' => 100.00,
            'cost_price' => 50.00,
            'status' => 'ACTIVE',
        ]);
    });

    /**
     * 建立已審核的採購單及明細
     */
    function createApprovedPurchaseOrder($supplier, $warehouse, $product, $userId): array
    {
        $purchaseOrder = PurchaseOrder::create([
            'po_no' => 'PO'.date('Ymd').'001',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => now(),
            'status' => 'APPROVED',
            'subtotal' => 500,
            'tax_amount' => 25,
            'total_amount' => 525,
            'created_by' => $userId,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        $poItem = PurchaseOrderItem::create([
            'po_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'received_quantity' => 0,
            'unit_price' => 50.00,
            'discount_rate' => 0,
            'line_total' => 500,
        ]);

        return ['purchaseOrder' => $purchaseOrder, 'poItem' => $poItem];
    }

    /*
    |--------------------------------------------------------------------------
    | 列表查詢測試
    |--------------------------------------------------------------------------
    */

    describe('GET /purchase-receipts', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $response = $this->getJson(apiUrl('purchase-receipts'));

            $response->assertStatus(401);
        });

        // 測試成功取得列表
        it('已認證時應返回收貨單列表', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 1000,
                'created_by' => $user->id,
            ]);

            $response = $this->getJson(apiUrl('purchase-receipts'));

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data',
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ])
                ->assertJson([
                    'success' => true,
                    'meta' => ['total' => 1],
                ]);
        });

        // 測試關鍵字搜尋
        it('應支援關鍵字搜尋', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 1000,
                'created_by' => $user->id,
            ]);

            $response = $this->getJson(apiUrl('purchase-receipts?keyword=GR20240101001'));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });

        // 測試供應商篩選
        it('應支援供應商篩選', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 1000,
                'created_by' => $user->id,
            ]);

            $response = $this->getJson(apiUrl("purchase-receipts?supplier_id={$this->supplier->id}"));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });

        // 測試狀態篩選
        it('應支援狀態篩選', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 1000,
                'created_by' => $user->id,
            ]);

            $response = $this->getJson(apiUrl('purchase-receipts?status=PENDING'));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });

        // 測試日期範圍篩選
        it('應支援日期範圍篩選', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 1000,
                'created_by' => $user->id,
            ]);

            $today = now()->toDateString();
            $response = $this->getJson(apiUrl("purchase-receipts?start_date={$today}&end_date={$today}"));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 新增測試
    |--------------------------------------------------------------------------
    */

    describe('POST /purchase-receipts', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $response = $this->postJson(apiUrl('purchase-receipts'), []);

            $response->assertStatus(401);
        });

        // 測試成功新增
        it('應成功新增收貨單', function () {
            $user = actingAsAuthenticatedUser();

            $result = createApprovedPurchaseOrder(
                $this->supplier,
                $this->warehouse,
                $this->product,
                $user->id
            );

            $data = [
                'purchase_order_id' => $result['purchaseOrder']->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now()->toDateString(),
                'remark' => '測試收貨備註',
                'items' => [
                    [
                        'po_item_id' => $result['poItem']->id,
                        'received_quantity' => 10,
                        'accepted_quantity' => 10,
                        'rejected_quantity' => 0,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-receipts'), $data);

            $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => '收貨單建立成功',
                ])
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'receipt_no',
                        'po_id',
                        'supplier_id',
                        'warehouse_id',
                        'status',
                        'total_amount',
                    ],
                ]);

            // 驗證資料庫
            $this->assertDatabaseHas('purchase_receipts', [
                'po_id' => $result['purchaseOrder']->id,
                'status' => 'PENDING',
            ]);
        });

        // 測試缺少必填欄位
        it('缺少採購單時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now()->toDateString(),
                'items' => [
                    [
                        'po_item_id' => 1,
                        'received_quantity' => 10,
                        'accepted_quantity' => 10,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-receipts'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['purchase_order_id']);
        });

        // 測試缺少倉庫
        it('缺少倉庫時應返回 422', function () {
            $user = actingAsAuthenticatedUser();

            $result = createApprovedPurchaseOrder(
                $this->supplier,
                $this->warehouse,
                $this->product,
                $user->id
            );

            $data = [
                'purchase_order_id' => $result['purchaseOrder']->id,
                'receipt_date' => now()->toDateString(),
                'items' => [
                    [
                        'po_item_id' => $result['poItem']->id,
                        'received_quantity' => 10,
                        'accepted_quantity' => 10,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-receipts'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['warehouse_id']);
        });

        // 測試缺少收貨日期
        it('缺少收貨日期時應返回 422', function () {
            $user = actingAsAuthenticatedUser();

            $result = createApprovedPurchaseOrder(
                $this->supplier,
                $this->warehouse,
                $this->product,
                $user->id
            );

            $data = [
                'purchase_order_id' => $result['purchaseOrder']->id,
                'warehouse_id' => $this->warehouse->id,
                'items' => [
                    [
                        'po_item_id' => $result['poItem']->id,
                        'received_quantity' => 10,
                        'accepted_quantity' => 10,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-receipts'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['receipt_date']);
        });

        // 測試缺少收貨明細
        it('缺少收貨明細時應返回 422', function () {
            $user = actingAsAuthenticatedUser();

            $result = createApprovedPurchaseOrder(
                $this->supplier,
                $this->warehouse,
                $this->product,
                $user->id
            );

            $data = [
                'purchase_order_id' => $result['purchaseOrder']->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now()->toDateString(),
            ];

            $response = $this->postJson(apiUrl('purchase-receipts'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['items']);
        });

        // 測試無效的採購單狀態
        it('採購單狀態無效時應返回 422', function () {
            $user = actingAsAuthenticatedUser();

            // 建立草稿狀態的採購單
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 500,
                'tax_amount' => 25,
                'total_amount' => 525,
                'created_by' => $user->id,
            ]);

            $poItem = PurchaseOrderItem::create([
                'po_id' => $purchaseOrder->id,
                'product_id' => $this->product->id,
                'quantity' => 10,
                'received_quantity' => 0,
                'unit_price' => 50.00,
                'discount_rate' => 0,
                'line_total' => 500,
            ]);

            $data = [
                'purchase_order_id' => $purchaseOrder->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now()->toDateString(),
                'items' => [
                    [
                        'po_item_id' => $poItem->id,
                        'received_quantity' => 10,
                        'accepted_quantity' => 10,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-receipts'), $data);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => '此採購單狀態無法進行收貨',
                ]);
        });

        // 測試收貨數量超過未收數量
        it('收貨數量超過未收數量時應返回 422', function () {
            $user = actingAsAuthenticatedUser();

            $result = createApprovedPurchaseOrder(
                $this->supplier,
                $this->warehouse,
                $this->product,
                $user->id
            );

            $data = [
                'purchase_order_id' => $result['purchaseOrder']->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now()->toDateString(),
                'items' => [
                    [
                        'po_item_id' => $result['poItem']->id,
                        'received_quantity' => 20, // 超過訂購數量 10
                        'accepted_quantity' => 20,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-receipts'), $data);

            $response->assertStatus(422);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 詳情查詢測試
    |--------------------------------------------------------------------------
    */

    describe('GET /purchase-receipts/{id}', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
            ]);

            $receipt = PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 1000,
            ]);

            $response = $this->getJson(apiUrl("purchase-receipts/{$receipt->id}"));

            $response->assertStatus(401);
        });

        // 測試成功取得詳情
        it('應成功取得收貨單詳情', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $receipt = PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 1000,
                'created_by' => $user->id,
            ]);

            $response = $this->getJson(apiUrl("purchase-receipts/{$receipt->id}"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '取得收貨單詳情成功',
                    'data' => [
                        'id' => $receipt->id,
                        'receipt_no' => 'GR20240101001',
                    ],
                ]);
        });

        // 測試找不到收貨單
        it('找不到收貨單時應返回 404', function () {
            actingAsAuthenticatedUser();

            $response = $this->getJson(apiUrl('purchase-receipts/99999'));

            $response->assertStatus(404);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 更新測試
    |--------------------------------------------------------------------------
    */

    describe('PUT /purchase-receipts/{id}', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
            ]);

            $receipt = PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 1000,
            ]);

            $response = $this->putJson(apiUrl("purchase-receipts/{$receipt->id}"), []);

            $response->assertStatus(401);
        });

        // 測試成功更新待確認狀態的收貨單
        it('應成功更新待確認狀態的收貨單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $receipt = PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 1000,
                'created_by' => $user->id,
            ]);

            $response = $this->putJson(apiUrl("purchase-receipts/{$receipt->id}"), [
                'remark' => '更新後的備註',
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '收貨單更新成功',
                ]);
        });

        // 測試無法更新非待確認狀態的收貨單
        it('無法更新非待確認狀態的收貨單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $receipt = PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'COMPLETED',
                'total_amount' => 1000,
                'created_by' => $user->id,
            ]);

            $response = $this->putJson(apiUrl("purchase-receipts/{$receipt->id}"), [
                'remark' => '嘗試更新',
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => '只能更新待確認狀態的收貨單',
                ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 刪除測試
    |--------------------------------------------------------------------------
    */

    describe('DELETE /purchase-receipts/{id}', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
            ]);

            $receipt = PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 1000,
            ]);

            $response = $this->deleteJson(apiUrl("purchase-receipts/{$receipt->id}"));

            $response->assertStatus(401);
        });

        // 測試成功刪除待確認狀態的收貨單
        it('應成功刪除待確認狀態的收貨單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $receipt = PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 1000,
                'created_by' => $user->id,
            ]);

            $response = $this->deleteJson(apiUrl("purchase-receipts/{$receipt->id}"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '收貨單刪除成功',
                ]);

            $this->assertDatabaseMissing('purchase_receipts', [
                'id' => $receipt->id,
            ]);
        });

        // 測試無法刪除已確認的收貨單
        it('無法刪除已確認的收貨單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $receipt = PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'COMPLETED',
                'total_amount' => 1000,
                'created_by' => $user->id,
            ]);

            $response = $this->deleteJson(apiUrl("purchase-receipts/{$receipt->id}"));

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => '只能刪除待確認狀態的收貨單',
                ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 取得收貨明細測試
    |--------------------------------------------------------------------------
    */

    describe('GET /purchase-receipts/{id}/items', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
            ]);

            $receipt = PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 1000,
            ]);

            $response = $this->getJson(apiUrl("purchase-receipts/{$receipt->id}/items"));

            $response->assertStatus(401);
        });

        // 測試成功取得收貨明細
        it('應成功取得收貨明細', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 500,
                'tax_amount' => 25,
                'total_amount' => 525,
                'created_by' => $user->id,
            ]);

            $poItem = PurchaseOrderItem::create([
                'po_id' => $purchaseOrder->id,
                'product_id' => $this->product->id,
                'quantity' => 10,
                'received_quantity' => 0,
                'unit_price' => 50.00,
                'discount_rate' => 0,
                'line_total' => 500,
            ]);

            $receipt = PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 500,
                'created_by' => $user->id,
            ]);

            PurchaseReceiptItem::create([
                'receipt_id' => $receipt->id,
                'po_item_id' => $poItem->id,
                'product_id' => $this->product->id,
                'expected_quantity' => 10,
                'received_quantity' => 10,
                'accepted_quantity' => 10,
                'rejected_quantity' => 0,
                'unit_price' => 50.00,
                'line_total' => 500,
            ]);

            $response = $this->getJson(apiUrl("purchase-receipts/{$receipt->id}/items"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '取得收貨明細成功',
                ])
                ->assertJsonCount(1, 'data');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 確認收貨測試
    |--------------------------------------------------------------------------
    */

    describe('PUT /purchase-receipts/{id}/confirm', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
            ]);

            $receipt = PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 1000,
            ]);

            $response = $this->putJson(apiUrl("purchase-receipts/{$receipt->id}/confirm"));

            $response->assertStatus(401);
        });

        // 測試成功確認收貨並更新庫存
        it('應成功確認收貨並更新庫存', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 500,
                'tax_amount' => 25,
                'total_amount' => 525,
                'created_by' => $user->id,
            ]);

            $poItem = PurchaseOrderItem::create([
                'po_id' => $purchaseOrder->id,
                'product_id' => $this->product->id,
                'quantity' => 10,
                'received_quantity' => 0,
                'unit_price' => 50.00,
                'discount_rate' => 0,
                'line_total' => 500,
            ]);

            $receipt = PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 500,
                'created_by' => $user->id,
            ]);

            PurchaseReceiptItem::create([
                'receipt_id' => $receipt->id,
                'po_item_id' => $poItem->id,
                'product_id' => $this->product->id,
                'expected_quantity' => 10,
                'received_quantity' => 10,
                'accepted_quantity' => 10,
                'rejected_quantity' => 0,
                'unit_price' => 50.00,
                'line_total' => 500,
            ]);

            $response = $this->putJson(apiUrl("purchase-receipts/{$receipt->id}/confirm"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '收貨確認成功，庫存已更新',
                    'data' => [
                        'status' => 'COMPLETED',
                    ],
                ]);

            // 驗證收貨單狀態
            $this->assertDatabaseHas('purchase_receipts', [
                'id' => $receipt->id,
                'status' => 'COMPLETED',
                'approved_by' => $user->id,
            ]);

            // 驗證庫存更新
            $this->assertDatabaseHas('inventory', [
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $this->product->id,
                'quantity' => 10,
            ]);

            // 驗證採購單明細已收數量更新
            $this->assertDatabaseHas('purchase_order_items', [
                'id' => $poItem->id,
                'received_quantity' => 10,
            ]);
        });

        // 測試確認收貨後採購單狀態更新為部分收貨
        it('部分收貨時採購單狀態應更新為 PARTIAL', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 500,
                'tax_amount' => 25,
                'total_amount' => 525,
                'created_by' => $user->id,
            ]);

            $poItem = PurchaseOrderItem::create([
                'po_id' => $purchaseOrder->id,
                'product_id' => $this->product->id,
                'quantity' => 10,
                'received_quantity' => 0,
                'unit_price' => 50.00,
                'discount_rate' => 0,
                'line_total' => 500,
            ]);

            $receipt = PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 250,
                'created_by' => $user->id,
            ]);

            // 只收 5 個（部分收貨）
            PurchaseReceiptItem::create([
                'receipt_id' => $receipt->id,
                'po_item_id' => $poItem->id,
                'product_id' => $this->product->id,
                'expected_quantity' => 10,
                'received_quantity' => 5,
                'accepted_quantity' => 5,
                'rejected_quantity' => 0,
                'unit_price' => 50.00,
                'line_total' => 250,
            ]);

            $response = $this->putJson(apiUrl("purchase-receipts/{$receipt->id}/confirm"));

            $response->assertStatus(200);

            // 驗證採購單狀態為部分收貨
            $this->assertDatabaseHas('purchase_orders', [
                'id' => $purchaseOrder->id,
                'status' => 'PARTIAL',
            ]);
        });

        // 測試確認收貨後採購單狀態更新為完成
        it('全部收貨時採購單狀態應更新為 COMPLETED', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 500,
                'tax_amount' => 25,
                'total_amount' => 525,
                'created_by' => $user->id,
            ]);

            $poItem = PurchaseOrderItem::create([
                'po_id' => $purchaseOrder->id,
                'product_id' => $this->product->id,
                'quantity' => 10,
                'received_quantity' => 0,
                'unit_price' => 50.00,
                'discount_rate' => 0,
                'line_total' => 500,
            ]);

            $receipt = PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'PENDING',
                'total_amount' => 500,
                'created_by' => $user->id,
            ]);

            // 收齊全部 10 個
            PurchaseReceiptItem::create([
                'receipt_id' => $receipt->id,
                'po_item_id' => $poItem->id,
                'product_id' => $this->product->id,
                'expected_quantity' => 10,
                'received_quantity' => 10,
                'accepted_quantity' => 10,
                'rejected_quantity' => 0,
                'unit_price' => 50.00,
                'line_total' => 500,
            ]);

            $response = $this->putJson(apiUrl("purchase-receipts/{$receipt->id}/confirm"));

            $response->assertStatus(200);

            // 驗證採購單狀態為完成
            $this->assertDatabaseHas('purchase_orders', [
                'id' => $purchaseOrder->id,
                'status' => 'COMPLETED',
            ]);
        });

        // 測試無法確認非待確認狀態的收貨單
        it('無法確認非待確認狀態的收貨單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'APPROVED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $receipt = PurchaseReceipt::create([
                'receipt_no' => 'GR20240101001',
                'po_id' => $purchaseOrder->id,
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'receipt_date' => now(),
                'status' => 'COMPLETED',
                'total_amount' => 1000,
                'created_by' => $user->id,
            ]);

            $response = $this->putJson(apiUrl("purchase-receipts/{$receipt->id}/confirm"));

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => '只能確認待確認狀態的收貨單',
                ]);
        });
    });
});
