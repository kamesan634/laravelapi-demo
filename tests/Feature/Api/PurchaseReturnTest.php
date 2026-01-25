<?php

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Models\Warehouse;

/**
 * 採購退貨單 API 測試
 *
 * 測試採購退貨的 CRUD 操作及審核出貨流程
 */
describe('採購退貨單 API', function () {

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
     * 建立庫存
     */
    function createInventory($warehouse, $product, $quantity): Inventory
    {
        return Inventory::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => $quantity,
            'reserved_quantity' => 0,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 列表查詢測試
    |--------------------------------------------------------------------------
    */

    describe('GET /purchase-returns', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $response = $this->getJson(apiUrl('purchase-returns'));

            $response->assertStatus(401);
        });

        // 測試成功取得列表
        it('已認證時應返回退貨單列表', function () {
            $user = actingAsAuthenticatedUser();

            PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
            ]);

            $response = $this->getJson(apiUrl('purchase-returns'));

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

            PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
            ]);

            $response = $this->getJson(apiUrl('purchase-returns?keyword=PR20240101001'));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });

        // 測試供應商篩選
        it('應支援供應商篩選', function () {
            $user = actingAsAuthenticatedUser();

            PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
            ]);

            $response = $this->getJson(apiUrl("purchase-returns?supplier_id={$this->supplier->id}"));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });

        // 測試狀態篩選
        it('應支援狀態篩選', function () {
            $user = actingAsAuthenticatedUser();

            PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
            ]);

            $response = $this->getJson(apiUrl('purchase-returns?status=PENDING'));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });

        // 測試退貨原因篩選
        it('應支援退貨原因篩選', function () {
            $user = actingAsAuthenticatedUser();

            PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
            ]);

            $response = $this->getJson(apiUrl('purchase-returns?return_reason=QUALITY'));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });

        // 測試日期範圍篩選
        it('應支援日期範圍篩選', function () {
            $user = actingAsAuthenticatedUser();

            PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
            ]);

            $today = now()->toDateString();
            $response = $this->getJson(apiUrl("purchase-returns?start_date={$today}&end_date={$today}"));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 新增測試
    |--------------------------------------------------------------------------
    */

    describe('POST /purchase-returns', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $response = $this->postJson(apiUrl('purchase-returns'), []);

            $response->assertStatus(401);
        });

        // 測試成功新增
        it('應成功新增退貨單', function () {
            $user = actingAsAuthenticatedUser();

            // 建立庫存
            createInventory($this->warehouse, $this->product, 100);

            $data = [
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now()->toDateString(),
                'return_reason' => 'QUALITY',
                'reason_detail' => '商品有瑕疵，需退貨給供應商',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'unit_price' => 50.00,
                        'reason' => '外觀損壞',
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-returns'), $data);

            $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                    'message' => '退貨單建立成功',
                ])
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'return_no',
                        'supplier_id',
                        'warehouse_id',
                        'return_reason',
                        'status',
                        'total_amount',
                    ],
                ]);

            // 驗證資料庫
            $this->assertDatabaseHas('purchase_returns', [
                'supplier_id' => $this->supplier->id,
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
            ]);
        });

        // 測試缺少必填欄位 - 供應商
        it('缺少供應商時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now()->toDateString(),
                'return_reason' => 'QUALITY',
                'reason_detail' => '商品有瑕疵',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'unit_price' => 50.00,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-returns'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['supplier_id']);
        });

        // 測試缺少倉庫
        it('缺少倉庫時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'return_date' => now()->toDateString(),
                'return_reason' => 'QUALITY',
                'reason_detail' => '商品有瑕疵',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'unit_price' => 50.00,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-returns'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['warehouse_id']);
        });

        // 測試缺少退貨日期
        it('缺少退貨日期時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_reason' => 'QUALITY',
                'reason_detail' => '商品有瑕疵',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'unit_price' => 50.00,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-returns'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['return_date']);
        });

        // 測試缺少退貨原因
        it('缺少退貨原因時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now()->toDateString(),
                'reason_detail' => '商品有瑕疵',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'unit_price' => 50.00,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-returns'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['return_reason']);
        });

        // 測試無效的退貨原因
        it('無效的退貨原因時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now()->toDateString(),
                'return_reason' => 'INVALID_REASON',
                'reason_detail' => '商品有瑕疵',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'unit_price' => 50.00,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-returns'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['return_reason']);
        });

        // 測試缺少原因說明
        it('缺少原因說明時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now()->toDateString(),
                'return_reason' => 'QUALITY',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'unit_price' => 50.00,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-returns'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['reason_detail']);
        });

        // 測試缺少退貨明細
        it('缺少退貨明細時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now()->toDateString(),
                'return_reason' => 'QUALITY',
                'reason_detail' => '商品有瑕疵',
            ];

            $response = $this->postJson(apiUrl('purchase-returns'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['items']);
        });

        // 測試庫存不足
        it('庫存不足時應返回 422', function () {
            $user = actingAsAuthenticatedUser();

            // 建立較少的庫存
            createInventory($this->warehouse, $this->product, 5);

            $data = [
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now()->toDateString(),
                'return_reason' => 'QUALITY',
                'reason_detail' => '商品有瑕疵',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10, // 超過庫存
                        'unit_price' => 50.00,
                    ],
                ],
            ];

            $response = $this->postJson(apiUrl('purchase-returns'), $data);

            $response->assertStatus(422);
        });

        // 測試各種退貨原因
        it('應支援各種有效的退貨原因', function () {
            $user = actingAsAuthenticatedUser();

            $reasons = ['QUALITY', 'WRONG_ITEM', 'DAMAGED', 'OVER_DELIVERY', 'OTHER'];

            foreach ($reasons as $reason) {
                createInventory($this->warehouse, $this->product, 100);

                $data = [
                    'supplier_id' => $this->supplier->id,
                    'warehouse_id' => $this->warehouse->id,
                    'return_date' => now()->toDateString(),
                    'return_reason' => $reason,
                    'reason_detail' => "測試原因：{$reason}",
                    'items' => [
                        [
                            'product_id' => $this->product->id,
                            'quantity' => 1,
                            'unit_price' => 50.00,
                        ],
                    ],
                ];

                $response = $this->postJson(apiUrl('purchase-returns'), $data);

                $response->assertStatus(201);
            }
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 詳情查詢測試
    |--------------------------------------------------------------------------
    */

    describe('GET /purchase-returns/{id}', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
            ]);

            $response = $this->getJson(apiUrl("purchase-returns/{$purchaseReturn->id}"));

            $response->assertStatus(401);
        });

        // 測試成功取得詳情
        it('應成功取得退貨單詳情', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
            ]);

            $response = $this->getJson(apiUrl("purchase-returns/{$purchaseReturn->id}"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '取得退貨單詳情成功',
                    'data' => [
                        'id' => $purchaseReturn->id,
                        'return_no' => 'PR20240101001',
                        'return_reason' => 'QUALITY',
                    ],
                ]);
        });

        // 測試找不到退貨單
        it('找不到退貨單時應返回 404', function () {
            actingAsAuthenticatedUser();

            $response = $this->getJson(apiUrl('purchase-returns/99999'));

            $response->assertStatus(404);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 更新測試
    |--------------------------------------------------------------------------
    */

    describe('PUT /purchase-returns/{id}', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
            ]);

            $response = $this->putJson(apiUrl("purchase-returns/{$purchaseReturn->id}"), []);

            $response->assertStatus(401);
        });

        // 測試成功更新待審核狀態的退貨單
        it('應成功更新待審核狀態的退貨單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
            ]);

            $response = $this->putJson(apiUrl("purchase-returns/{$purchaseReturn->id}"), [
                'reason_detail' => '更新後的原因說明',
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '退貨單更新成功',
                ]);
        });

        // 測試無法更新非待審核狀態的退貨單
        it('無法更新非待審核狀態的退貨單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'APPROVED',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
            ]);

            $response = $this->putJson(apiUrl("purchase-returns/{$purchaseReturn->id}"), [
                'reason_detail' => '嘗試更新',
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => '只能更新待審核狀態的退貨單',
                ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 刪除測試
    |--------------------------------------------------------------------------
    */

    describe('DELETE /purchase-returns/{id}', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
            ]);

            $response = $this->deleteJson(apiUrl("purchase-returns/{$purchaseReturn->id}"));

            $response->assertStatus(401);
        });

        // 測試成功刪除待審核狀態的退貨單
        it('應成功刪除待審核狀態的退貨單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
            ]);

            $response = $this->deleteJson(apiUrl("purchase-returns/{$purchaseReturn->id}"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '退貨單刪除成功',
                ]);

            $this->assertDatabaseMissing('purchase_returns', [
                'id' => $purchaseReturn->id,
            ]);
        });

        // 測試無法刪除已審核的退貨單
        it('無法刪除已審核的退貨單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'APPROVED',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
            ]);

            $response = $this->deleteJson(apiUrl("purchase-returns/{$purchaseReturn->id}"));

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => '只能刪除待審核狀態的退貨單',
                ]);
        });

        // 測試無法刪除已出貨的退貨單
        it('無法刪除已出貨的退貨單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'SHIPPED',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
            ]);

            $response = $this->deleteJson(apiUrl("purchase-returns/{$purchaseReturn->id}"));

            $response->assertStatus(422);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 取得退貨明細測試
    |--------------------------------------------------------------------------
    */

    describe('GET /purchase-returns/{id}/items', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
            ]);

            $response = $this->getJson(apiUrl("purchase-returns/{$purchaseReturn->id}/items"));

            $response->assertStatus(401);
        });

        // 測試成功取得退貨明細
        it('應成功取得退貨明細', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
            ]);

            PurchaseReturnItem::create([
                'return_id' => $purchaseReturn->id,
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'quantity' => 10,
                'unit_price' => 50.00,
                'line_total' => 500,
                'reason' => '外觀損壞',
            ]);

            $response = $this->getJson(apiUrl("purchase-returns/{$purchaseReturn->id}/items"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '取得退貨明細成功',
                ])
                ->assertJsonCount(1, 'data');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 審核測試
    |--------------------------------------------------------------------------
    */

    describe('PUT /purchase-returns/{id}/approve', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
            ]);

            $response = $this->putJson(apiUrl("purchase-returns/{$purchaseReturn->id}/approve"));

            $response->assertStatus(401);
        });

        // 測試成功審核通過
        it('應成功審核通過待審核的退貨單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
            ]);

            $response = $this->putJson(apiUrl("purchase-returns/{$purchaseReturn->id}/approve"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '退貨單審核通過',
                    'data' => [
                        'status' => 'APPROVED',
                    ],
                ]);

            $this->assertDatabaseHas('purchase_returns', [
                'id' => $purchaseReturn->id,
                'status' => 'APPROVED',
                'approved_by' => $user->id,
            ]);
        });

        // 測試無法審核非待審核狀態的退貨單
        it('無法審核非待審核狀態的退貨單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'APPROVED',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
            ]);

            $response = $this->putJson(apiUrl("purchase-returns/{$purchaseReturn->id}/approve"));

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => '只能審核待審核狀態的退貨單',
                ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 出貨測試
    |--------------------------------------------------------------------------
    */

    describe('PUT /purchase-returns/{id}/ship', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'APPROVED',
                'total_amount' => 500,
                'notes' => '品質問題',
            ]);

            $response = $this->putJson(apiUrl("purchase-returns/{$purchaseReturn->id}/ship"));

            $response->assertStatus(401);
        });

        // 測試成功出貨並扣減庫存
        it('應成功出貨並扣減庫存', function () {
            $user = actingAsAuthenticatedUser();

            // 建立庫存
            $inventory = createInventory($this->warehouse, $this->product, 100);

            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'APPROVED',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            PurchaseReturnItem::create([
                'return_id' => $purchaseReturn->id,
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'quantity' => 10,
                'unit_price' => 50.00,
                'line_total' => 500,
            ]);

            $response = $this->putJson(apiUrl("purchase-returns/{$purchaseReturn->id}/ship"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => '退貨出貨成功，庫存已扣減',
                    'data' => [
                        'status' => 'SHIPPED',
                    ],
                ]);

            // 驗證狀態更新
            $this->assertDatabaseHas('purchase_returns', [
                'id' => $purchaseReturn->id,
                'status' => 'SHIPPED',
            ]);

            // 驗證庫存扣減
            $this->assertDatabaseHas('inventory', [
                'id' => $inventory->id,
                'quantity' => 90, // 100 - 10
            ]);
        });

        // 測試無法出貨非已審核狀態的退貨單
        it('無法出貨非已審核狀態的退貨單', function () {
            $user = actingAsAuthenticatedUser();

            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'PENDING',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
            ]);

            $response = $this->putJson(apiUrl("purchase-returns/{$purchaseReturn->id}/ship"));

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => '只能出貨已審核通過的退貨單',
                ]);
        });

        // 測試庫存不足無法出貨
        it('庫存不足時無法出貨', function () {
            $user = actingAsAuthenticatedUser();

            // 建立較少的庫存
            createInventory($this->warehouse, $this->product, 5);

            $purchaseReturn = PurchaseReturn::create([
                'return_no' => 'PR20240101001',
                'supplier_id' => $this->supplier->id,
                'warehouse_id' => $this->warehouse->id,
                'return_date' => now(),
                'return_reason' => 'QUALITY',
                'status' => 'APPROVED',
                'total_amount' => 500,
                'notes' => '品質問題',
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            PurchaseReturnItem::create([
                'return_id' => $purchaseReturn->id,
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'quantity' => 10, // 超過庫存
                'unit_price' => 50.00,
                'line_total' => 500,
            ]);

            $response = $this->putJson(apiUrl("purchase-returns/{$purchaseReturn->id}/ship"));

            $response->assertStatus(422);
        });
    });
});
