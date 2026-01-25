<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceipt;
use App\Models\Supplier;
use App\Models\Warehouse;

/**
 * 採購單 API 測試
 *
 * 測試採購單的 CRUD 操作及審核流程
 */
describe('採購單 API', function () {

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

    /*
    |--------------------------------------------------------------------------
    | 列表查詢測試
    |--------------------------------------------------------------------------
    */

    describe('GET /purchase-orders', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $response = $this->getJson(apiUrl('purchase-orders'));

            $response->assertStatus(401);
        });

        // 測試成功取得列表
        it('已認證時應返回採購單列表', function () {
            actingAsAuthenticatedUser();

            // 建立測試採購單
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => auth()->id(),
            ]);

            $response = $this->getJson(apiUrl('purchase-orders'));

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
            actingAsAuthenticatedUser();

            PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => auth()->id(),
            ]);

            $response = $this->getJson(apiUrl('purchase-orders?keyword=PO20240101001'));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });

        // 測試供應商篩選
        it('應支援供應商篩選', function () {
            actingAsAuthenticatedUser();

            PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => auth()->id(),
            ]);

            $response = $this->getJson(apiUrl("purchase-orders?supplier_id={$this->supplier->id}"));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });

        // 測試狀態篩選
        it('應支援狀態篩選', function () {
            actingAsAuthenticatedUser();

            PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => auth()->id(),
            ]);

            $response = $this->getJson(apiUrl('purchase-orders?status=DRAFT'));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });

        // 測試日期範圍篩選
        it('應支援日期範圍篩選', function () {
            actingAsAuthenticatedUser();

            PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => auth()->id(),
            ]);

            $today = now()->toDateString();
            $response = $this->getJson(apiUrl("purchase-orders?start_date={$today}&end_date={$today}"));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 新增測試
    |--------------------------------------------------------------------------
    */

    describe('POST /purchase-orders', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $response = $this->postJson(apiUrl('purchase-orders'), []);

            $response->assertStatus(401);
        });

        // 測試成功新增
        it('應成功新增採購單', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now()->toDateString(),
                'expected_date' => now()->addDays(7)->toDateString(),
                'remark' => '測試備註',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'unit_price' => 50.00,
                        'tax_rate' => 5,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-orders'), $data);

            $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => '採購單建立成功',
                ])
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'po_no',
                        'supplier_id',
                        'warehouse_id',
                        'status',
                        'subtotal',
                        'tax_amount',
                        'total_amount',
                    ],
                ]);

            // 驗證資料庫
            $this->assertDatabaseHas('purchase_orders', [
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'DRAFT',
            ]);
        });

        // 測試缺少必填欄位
        it('缺少供應商時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'unit_price' => 50.00,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-orders'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['supplier_id']);
        });

        // 測試缺少倉庫
        it('缺少倉庫時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'order_date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'unit_price' => 50.00,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-orders'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['warehouse_id']);
        });

        // 測試缺少採購日期
        it('缺少採購日期時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'unit_price' => 50.00,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-orders'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['order_date']);
        });

        // 測試缺少採購明細
        it('缺少採購明細時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now()->toDateString(),
            ];

            $response = $this->postJson(apiUrl('purchase-orders'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['items']);
        });

        // 測試無效的供應商 ID
        it('無效的供應商 ID 時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => 99999,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'unit_price' => 50.00,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-orders'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['supplier_id']);
        });

        // 測試預期日期必須在採購日期之後
        it('預期日期早於採購日期時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now()->toDateString(),
                'expected_date' => now()->subDays(1)->toDateString(),
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'unit_price' => 50.00,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-orders'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['expected_date']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 詳情查詢測試
    |--------------------------------------------------------------------------
    */

    describe('GET /purchase-orders/{id}', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
            ]);

            $response = $this->getJson(apiUrl("purchase-orders/{$purchaseOrder->id}"));

            $response->assertStatus(401);
        });

        // 測試成功取得詳情
        it('應成功取得採購單詳情', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $response = $this->getJson(apiUrl("purchase-orders/{$purchaseOrder->id}"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '取得採購單詳情成功',
                    'data' => [
                        'id' => $purchaseOrder->id,
                        'po_no' => 'PO20240101001',
                    ],
                ]);
        });

        // 測試找不到採購單
        it('找不到採購單時應返回 404', function () {
            actingAsAuthenticatedUser();

            $response = $this->getJson(apiUrl('purchase-orders/99999'));

            $response->assertStatus(404);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 更新測試
    |--------------------------------------------------------------------------
    */

    describe('PUT /purchase-orders/{id}', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
            ]);

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}"), []);

            $response->assertStatus(401);
        });

        // 測試成功更新草稿狀態的採購單
        it('應成功更新草稿狀態的採購單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $data = [
                'expected_date' => now()->addDays(14)->toDateString(),
                'remark' => '更新後的備註',
            ];

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}"), $data);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '採購單更新成功',
                ]);
        });

        // 測試無法更新非草稿狀態的採購單
        it('無法更新非草稿狀態的採購單', function () {
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

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}"), [
                'remark' => '嘗試更新',
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => '只能更新草稿狀態的採購單',
                ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 刪除測試
    |--------------------------------------------------------------------------
    */

    describe('DELETE /purchase-orders/{id}', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
            ]);

            $response = $this->deleteJson(apiUrl("purchase-orders/{$purchaseOrder->id}"));

            $response->assertStatus(401);
        });

        // 測試成功刪除草稿狀態的採購單
        it('應成功刪除草稿狀態的採購單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $response = $this->deleteJson(apiUrl("purchase-orders/{$purchaseOrder->id}"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '採購單刪除成功',
                ]);

            $this->assertDatabaseMissing('purchase_orders', [
                'id' => $purchaseOrder->id,
            ]);
        });

        // 測試成功刪除已取消的採購單
        it('應成功刪除已取消的採購單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'CANCELLED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $response = $this->deleteJson(apiUrl("purchase-orders/{$purchaseOrder->id}"));

            $response->assertStatus(200);
        });

        // 測試無法刪除已審核的採購單
        it('無法刪除已審核的採購單', function () {
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

            $response = $this->deleteJson(apiUrl("purchase-orders/{$purchaseOrder->id}"));

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => '只能刪除草稿或已取消的採購單',
                ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 取得採購明細測試
    |--------------------------------------------------------------------------
    */

    describe('GET /purchase-orders/{id}/items', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
            ]);

            $response = $this->getJson(apiUrl("purchase-orders/{$purchaseOrder->id}/items"));

            $response->assertStatus(401);
        });

        // 測試成功取得採購明細
        it('應成功取得採購明細', function () {
            $user = actingAsAuthenticatedUser();

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

            PurchaseOrderItem::create([
                'po_id' => $purchaseOrder->id,
                'product_id' => $this->product->id,
                'quantity' => 10,
                'received_quantity' => 0,
                'unit_price' => 50.00,
                'discount_rate' => 0,
                'line_total' => 500,
            ]);

            $response = $this->getJson(apiUrl("purchase-orders/{$purchaseOrder->id}/items"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '取得採購明細成功',
                ])
                ->assertJsonCount(1, 'data');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 送審測試
    |--------------------------------------------------------------------------
    */

    describe('PUT /purchase-orders/{id}/submit', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
            ]);

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}/submit"));

            $response->assertStatus(401);
        });

        // 測試成功送審
        it('應成功送審草稿狀態的採購單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}/submit"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '採購單已送審',
                    'data' => [
                        'status' => 'PENDING',
                    ],
                ]);

            $this->assertDatabaseHas('purchase_orders', [
                'id' => $purchaseOrder->id,
                'status' => 'PENDING',
            ]);
        });

        // 測試無法送審非草稿狀態的採購單
        it('無法送審非草稿狀態的採購單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'PENDING',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}/submit"));

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => '只能送審草稿狀態的採購單',
                ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 審核通過測試
    |--------------------------------------------------------------------------
    */

    describe('PUT /purchase-orders/{id}/approve', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'PENDING',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
            ]);

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}/approve"));

            $response->assertStatus(401);
        });

        // 測試成功審核通過
        it('應成功審核通過待審核的採購單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'PENDING',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}/approve"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '採購單審核通過',
                    'data' => [
                        'status' => 'APPROVED',
                    ],
                ]);

            $this->assertDatabaseHas('purchase_orders', [
                'id' => $purchaseOrder->id,
                'status' => 'APPROVED',
                'approved_by' => $user->id,
            ]);
        });

        // 測試無法審核非待審核狀態的採購單
        it('無法審核非待審核狀態的採購單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}/approve"));

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => '只能審核待審核狀態的採購單',
                ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 審核駁回測試
    |--------------------------------------------------------------------------
    */

    describe('PUT /purchase-orders/{id}/reject', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'PENDING',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
            ]);

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}/reject"));

            $response->assertStatus(401);
        });

        // 測試成功駁回
        it('應成功駁回待審核的採購單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'PENDING',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}/reject"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '採購單已駁回',
                    'data' => [
                        'status' => 'CANCELLED',
                    ],
                ]);

            $this->assertDatabaseHas('purchase_orders', [
                'id' => $purchaseOrder->id,
                'status' => 'CANCELLED',
            ]);
        });

        // 測試無法駁回非待審核狀態的採購單
        it('無法駁回非待審核狀態的採購單', function () {
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

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}/reject"));

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => '只能駁回待審核狀態的採購單',
                ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 取消測試
    |--------------------------------------------------------------------------
    */

    describe('PUT /purchase-orders/{id}/cancel', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
            ]);

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}/cancel"));

            $response->assertStatus(401);
        });

        // 測試成功取消草稿狀態的採購單
        it('應成功取消草稿狀態的採購單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'DRAFT',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}/cancel"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '採購單已取消',
                    'data' => [
                        'status' => 'CANCELLED',
                    ],
                ]);
        });

        // 測試成功取消待審核狀態的採購單
        it('應成功取消待審核狀態的採購單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'PENDING',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}/cancel"));

            $response->assertStatus(200);
        });

        // 測試成功取消已審核狀態的採購單（無收貨記錄）
        it('應成功取消已審核狀態的採購單（無收貨記錄）', function () {
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

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}/cancel"));

            $response->assertStatus(200);
        });

        // 測試無法取消已有收貨記錄的採購單
        it('無法取消已有收貨記錄的採購單', function () {
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

            // 建立收貨記錄
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

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}/cancel"));

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => '此採購單已有收貨記錄，無法取消',
                ]);
        });

        // 測試無法取消已完成的採購單
        it('無法取消已完成的採購單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseOrder = PurchaseOrder::create([
                'po_no' => 'PO20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'order_date' => now(),
                'status' => 'COMPLETED',
                'subtotal' => 1000,
                'tax_amount' => 50,
                'total_amount' => 1050,
                'created_by' => $user->id,
            ]);

            $response = $this->putJson(apiUrl("purchase-orders/{$purchaseOrder->id}/cancel"));

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => '此狀態的採購單無法取消',
                ]);
        });
    });
});
