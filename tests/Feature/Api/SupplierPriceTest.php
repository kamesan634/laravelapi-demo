<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierPrice;

/**
 * 供應商報價 API 測試
 *
 * 測試供應商報價的 CRUD 操作及報價歷史查詢
 *
 * 注意：SupplierPriceController 目前僅有 TODO 佔位，
 * 這些測試用於驗證 API 端點和預期行為
 */
describe('供應商報價 API', function () {

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

    describe('GET /supplier-prices', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $response = $this->getJson(apiUrl('supplier-prices'));

            $response->assertStatus(401);
        });

        // 測試成功取得列表（待實作）
        it('已認證時應返回報價列表', function () {
            $user = actingAsAuthenticatedUser();

            // 建立測試報價
            SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'min_quantity' => 10,
                'lead_days' => 3,
                'effective_from' => now(),
                'effective_to' => now()->addMonths(6),
                'is_primary' => true,
                'is_active' => true,
            ]);

            $response = $this->getJson(apiUrl('supplier-prices'));

            // 由於控制器尚未實作，可能返回 500 或其他狀態
            // 這裡測試端點存在且需要認證
            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data',
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 新增測試
    |--------------------------------------------------------------------------
    */

    describe('POST /supplier-prices', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $response = $this->postJson(apiUrl('supplier-prices'), []);

            $response->assertStatus(401);
        });

        // 測試成功新增（待實作）
        it('應成功新增報價', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'min_quantity' => 10,
                'lead_time_days' => 3,
                'effective_from' => now()->toDateString(),
                'effective_to' => now()->addMonths(6)->toDateString(),
                'is_preferred' => true,
                'remark' => '優惠價格',
            ];

            $response = $this->postJson(apiUrl('supplier-prices'), $data);

            $response->assertStatus(201)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'supplier_id',
                        'product_id',
                        'unit_price',
                    ],
                ]);
        });

        // 測試驗證規則 - 缺少供應商
        it('缺少供應商時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now()->toDateString(),
            ];

            $response = $this->postJson(apiUrl('supplier-prices'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['supplier_id']);
        });

        // 測試驗證規則 - 缺少商品
        it('缺少商品時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'unit_price' => 45.00,
                'effective_from' => now()->toDateString(),
            ];

            $response = $this->postJson(apiUrl('supplier-prices'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['product_id']);
        });

        // 測試驗證規則 - 缺少單價
        it('缺少單價時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'effective_from' => now()->toDateString(),
            ];

            $response = $this->postJson(apiUrl('supplier-prices'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['unit_price']);
        });

        // 測試驗證規則 - 缺少生效日期
        it('缺少生效日期時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
            ];

            $response = $this->postJson(apiUrl('supplier-prices'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['effective_from']);
        });

        // 測試驗證規則 - 無效的供應商 ID
        it('無效的供應商 ID 時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => 99999,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now()->toDateString(),
            ];

            $response = $this->postJson(apiUrl('supplier-prices'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['supplier_id']);
        });

        // 測試驗證規則 - 無效的商品 ID
        it('無效的商品 ID 時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'product_id' => 99999,
                'unit_price' => 45.00,
                'effective_from' => now()->toDateString(),
            ];

            $response = $this->postJson(apiUrl('supplier-prices'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['product_id']);
        });

        // 測試驗證規則 - 負數單價
        it('負數單價時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => -10.00,
                'effective_from' => now()->toDateString(),
            ];

            $response = $this->postJson(apiUrl('supplier-prices'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['unit_price']);
        });

        // 測試驗證規則 - 失效日期在生效日期之前
        it('失效日期在生效日期之前時應返回 422', function () {
            actingAsAuthenticatedUser();

            $data = [
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now()->toDateString(),
                'effective_to' => now()->subDays(1)->toDateString(),
            ];

            $response = $this->postJson(apiUrl('supplier-prices'), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['effective_to']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 詳情查詢測試
    |--------------------------------------------------------------------------
    */

    describe('GET /supplier-prices/{id}', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $supplierPrice = SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now(),
                'is_active' => true,
            ]);

            $response = $this->getJson(apiUrl("supplier-prices/{$supplierPrice->id}"));

            $response->assertStatus(401);
        });

        // 測試成功取得詳情（待實作）
        it('應成功取得報價詳情', function () {
            actingAsAuthenticatedUser();

            $supplierPrice = SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'min_quantity' => 10,
                'lead_days' => 3,
                'effective_from' => now(),
                'is_primary' => true,
                'is_active' => true,
            ]);

            $response = $this->getJson(apiUrl("supplier-prices/{$supplierPrice->id}"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $supplierPrice->id,
                        'supplier_id' => $this->supplier->id,
                        'product_id' => $this->product->id,
                    ],
                ]);
        });

        // 測試找不到報價
        it('找不到報價時應返回 404', function () {
            actingAsAuthenticatedUser();

            $response = $this->getJson(apiUrl('supplier-prices/99999'));

            $response->assertStatus(404);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 更新測試
    |--------------------------------------------------------------------------
    */

    describe('PUT /supplier-prices/{id}', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $supplierPrice = SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now(),
                'is_active' => true,
            ]);

            $response = $this->putJson(apiUrl("supplier-prices/{$supplierPrice->id}"), []);

            $response->assertStatus(401);
        });

        // 測試成功更新（待實作）
        it('應成功更新報價', function () {
            actingAsAuthenticatedUser();

            $supplierPrice = SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now(),
                'is_active' => true,
            ]);

            $data = [
                'unit_price' => 42.00,
                'min_quantity' => 20,
                'lead_time_days' => 5,
                'remark' => '更新後的備註',
            ];

            $response = $this->putJson(apiUrl("supplier-prices/{$supplierPrice->id}"), $data);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);
        });

        // 測試更新時失效日期驗證
        it('更新時失效日期在生效日期之前應返回 422', function () {
            actingAsAuthenticatedUser();

            $supplierPrice = SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now(),
                'is_active' => true,
            ]);

            $data = [
                'effective_from' => now()->toDateString(),
                'effective_to' => now()->subDays(1)->toDateString(),
            ];

            $response = $this->putJson(apiUrl("supplier-prices/{$supplierPrice->id}"), $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['effective_to']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 刪除測試
    |--------------------------------------------------------------------------
    */

    describe('DELETE /supplier-prices/{id}', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $supplierPrice = SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now(),
                'is_active' => true,
            ]);

            $response = $this->deleteJson(apiUrl("supplier-prices/{$supplierPrice->id}"));

            $response->assertStatus(401);
        });

        // 測試成功刪除（待實作）
        it('應成功刪除報價', function () {
            actingAsAuthenticatedUser();

            $supplierPrice = SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now(),
                'is_active' => true,
            ]);

            $response = $this->deleteJson(apiUrl("supplier-prices/{$supplierPrice->id}"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ]);

            $this->assertDatabaseMissing('supplier_prices', [
                'id' => $supplierPrice->id,
            ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 報價歷史測試
    |--------------------------------------------------------------------------
    */

    describe('GET /supplier-prices/{id}/history', function () {

        // 測試未認證存取
        it('未認證時應返回 401', function () {
            $supplierPrice = SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now(),
                'is_active' => true,
            ]);

            $response = $this->getJson(apiUrl("supplier-prices/{$supplierPrice->id}/history"));

            $response->assertStatus(401);
        });

        // 測試成功取得報價歷史（待實作）
        it('應成功取得報價歷史', function () {
            actingAsAuthenticatedUser();

            $supplierPrice = SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now(),
                'is_active' => true,
            ]);

            $response = $this->getJson(apiUrl("supplier-prices/{$supplierPrice->id}/history"));

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'data',
                ]);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 模型方法測試
    |--------------------------------------------------------------------------
    */

    describe('SupplierPrice Model', function () {

        // 測試報價有效性檢查 - 有效報價
        it('isValid() 應正確判斷有效報價', function () {
            actingAsAuthenticatedUser();

            $supplierPrice = SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now()->subDays(1),
                'effective_to' => now()->addDays(30),
                'is_active' => true,
            ]);

            expect($supplierPrice->isValid())->toBeTrue();
        });

        // 測試報價有效性檢查 - 尚未生效
        it('isValid() 應正確判斷尚未生效的報價', function () {
            actingAsAuthenticatedUser();

            $supplierPrice = SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now()->addDays(1),
                'effective_to' => now()->addDays(30),
                'is_active' => true,
            ]);

            expect($supplierPrice->isValid())->toBeFalse();
        });

        // 測試報價有效性檢查 - 已過期
        it('isValid() 應正確判斷已過期的報價', function () {
            actingAsAuthenticatedUser();

            $supplierPrice = SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now()->subDays(30),
                'effective_to' => now()->subDays(1),
                'is_active' => true,
            ]);

            expect($supplierPrice->isValid())->toBeFalse();
        });

        // 測試報價有效性檢查 - 已停用
        it('isValid() 應正確判斷已停用的報價', function () {
            actingAsAuthenticatedUser();

            $supplierPrice = SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now()->subDays(1),
                'effective_to' => now()->addDays(30),
                'is_active' => false,
            ]);

            expect($supplierPrice->isValid())->toBeFalse();
        });

        // 測試報價有效性檢查 - 無失效日期
        it('isValid() 應正確判斷無失效日期的有效報價', function () {
            actingAsAuthenticatedUser();

            $supplierPrice = SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now()->subDays(1),
                'effective_to' => null,
                'is_active' => true,
            ]);

            expect($supplierPrice->isValid())->toBeTrue();
        });

        // 測試供應商關聯
        it('應正確取得供應商關聯', function () {
            actingAsAuthenticatedUser();

            $supplierPrice = SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now(),
                'is_active' => true,
            ]);

            expect($supplierPrice->supplier)->not->toBeNull();
            expect($supplierPrice->supplier->id)->toBe($this->supplier->id);
            expect($supplierPrice->supplier->name)->toBe('測試供應商');
        });

        // 測試商品關聯
        it('應正確取得商品關聯', function () {
            actingAsAuthenticatedUser();

            $supplierPrice = SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now(),
                'is_active' => true,
            ]);

            expect($supplierPrice->product)->not->toBeNull();
            expect($supplierPrice->product->id)->toBe($this->product->id);
            expect($supplierPrice->product->name)->toBe('測試商品');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | 篩選功能測試（待實作）
    |--------------------------------------------------------------------------
    */

    describe('篩選功能', function () {

        // 測試供應商篩選
        it('應支援供應商篩選', function () {
            $user = actingAsAuthenticatedUser();

            SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now(),
                'is_active' => true,
            ]);

            $response = $this->getJson(apiUrl("supplier-prices?supplier_id={$this->supplier->id}"));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });

        // 測試商品篩選
        it('應支援商品篩選', function () {
            $user = actingAsAuthenticatedUser();

            SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now(),
                'is_active' => true,
            ]);

            $response = $this->getJson(apiUrl("supplier-prices?product_id={$this->product->id}"));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });

        // 測試有效報價篩選
        it('應支援有效報價篩選', function () {
            $user = actingAsAuthenticatedUser();

            // 有效報價
            SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now()->subDays(1),
                'effective_to' => now()->addDays(30),
                'is_active' => true,
            ]);

            // 已過期報價
            SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 40.00,
                'effective_from' => now()->subDays(60),
                'effective_to' => now()->subDays(30),
                'is_active' => true,
            ]);

            $response = $this->getJson(apiUrl('supplier-prices?is_valid=1'));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });

        // 測試主要報價篩選
        it('應支援主要報價篩選', function () {
            $user = actingAsAuthenticatedUser();

            // 主要報價
            SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 45.00,
                'effective_from' => now(),
                'is_primary' => true,
                'is_active' => true,
            ]);

            // 非主要報價
            SupplierPrice::create([
                'supplier_id' => $this->supplier->id,
                'product_id' => $this->product->id,
                'unit_price' => 48.00,
                'effective_from' => now(),
                'is_primary' => false,
                'is_active' => true,
            ]);

            $response = $this->getJson(apiUrl('supplier-prices?is_primary=1'));

            $response->assertStatus(200)
                ->assertJson(['meta' => ['total' => 1]]);
        });
    });
});
