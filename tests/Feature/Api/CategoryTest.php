<?php

/**
 * 商品分類 API 測試
 *
 * 測試商品分類資料的 CRUD 操作，包含樹狀結構功能
 */

use App\Models\Category;

// ==================== 列表測試 ====================

// 測試取得分類列表
it('已認證使用者可以取得分類列表', function () {
    actingAsAuthenticatedUser();

    Category::create(['code' => 'C001', 'name' => '食品', 'status' => 'ACTIVE']);
    Category::create(['code' => 'C002', 'name' => '飲料', 'status' => 'ACTIVE']);

    $response = $this->getJson(apiUrl('categories'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => ['id', 'code', 'name', 'level', 'status', 'created_at', 'updated_at'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

// 測試未認證取得分類列表
it('未認證使用者取得分類列表會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('categories'));

    $response->assertStatus(401);
});

// 測試搜尋分類列表
it('可以透過關鍵字搜尋分類', function () {
    actingAsAuthenticatedUser();

    Category::create(['code' => 'C001', 'name' => '食品', 'status' => 'ACTIVE']);
    Category::create(['code' => 'C002', 'name' => '飲料', 'status' => 'ACTIVE']);

    $response = $this->getJson(apiUrl('categories?keyword=食品'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試只取得根分類
it('可以篩選只顯示根分類', function () {
    actingAsAuthenticatedUser();

    $parent = Category::create(['code' => 'C001', 'name' => '食品', 'status' => 'ACTIVE']);
    Category::create([
        'code' => 'C002',
        'name' => '零食',
        'parent_id' => $parent->id,
        'level' => 2,
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('categories?root_only=1'));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// 測試取得父分類下的子分類
it('可以透過父分類篩選子分類', function () {
    actingAsAuthenticatedUser();

    $parent = Category::create(['code' => 'C001', 'name' => '食品', 'status' => 'ACTIVE']);
    Category::create([
        'code' => 'C002',
        'name' => '零食',
        'parent_id' => $parent->id,
        'level' => 2,
        'status' => 'ACTIVE',
    ]);
    Category::create([
        'code' => 'C003',
        'name' => '飲料',
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("categories?parent_id={$parent->id}"));

    $response->assertStatus(200);
    $this->assertEquals(1, $response->json('meta.total'));
});

// ==================== 新增測試 ====================

// 測試成功新增根分類
it('已認證使用者可以新增根分類', function () {
    actingAsAuthenticatedUser();

    $categoryData = [
        'code' => 'C001',
        'name' => '新分類',
        'sort_order' => 1,
        'status' => 'ACTIVE',
    ];

    $response = $this->postJson(apiUrl('categories'), $categoryData);

    $response->assertStatus(201)
        ->assertJsonPath('data.code', 'C001')
        ->assertJsonPath('data.name', '新分類')
        ->assertJsonPath('data.level', 1);

    $this->assertDatabaseHas('categories', [
        'code' => 'C001',
        'name' => '新分類',
        'level' => 1,
    ]);
});

// 測試成功新增子分類
it('已認證使用者可以新增子分類', function () {
    actingAsAuthenticatedUser();

    $parent = Category::create(['code' => 'C001', 'name' => '父分類', 'status' => 'ACTIVE']);

    $categoryData = [
        'code' => 'C002',
        'name' => '子分類',
        'parent_id' => $parent->id,
        'status' => 'ACTIVE',
    ];

    $response = $this->postJson(apiUrl('categories'), $categoryData);

    $response->assertStatus(201)
        ->assertJsonPath('data.level', 2)
        ->assertJsonPath('data.parent_id', $parent->id);
});

// 測試新增分類驗證錯誤 - 缺少必填欄位
it('新增分類時缺少必填欄位會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('categories'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code', 'name']);
});

// 測試新增分類驗證錯誤 - 代碼重複
it('新增分類時代碼重複會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    Category::create(['code' => 'C001', 'name' => '現有分類', 'status' => 'ACTIVE']);

    $response = $this->postJson(apiUrl('categories'), [
        'code' => 'C001',
        'name' => '新分類',
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

// 測試新增分類驗證錯誤 - 父分類不存在
it('新增分類時父分類不存在會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('categories'), [
        'code' => 'C001',
        'name' => '新分類',
        'parent_id' => 99999,
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['parent_id']);
});

// 測試未認證新增分類
it('未認證使用者新增分類會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl('categories'), [
        'code' => 'C001',
        'name' => '新分類',
        'status' => 'ACTIVE',
    ]);

    $response->assertStatus(401);
});

// ==================== 查看單一分類測試 ====================

// 測試取得單一分類
it('已認證使用者可以取得單一分類詳情', function () {
    actingAsAuthenticatedUser();

    $category = Category::create([
        'code' => 'C001',
        'name' => '測試分類',
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("categories/{$category->id}"));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $category->id)
        ->assertJsonPath('data.code', 'C001')
        ->assertJsonPath('data.name', '測試分類');
});

// 測試取得不存在的分類
it('取得不存在的分類會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('categories/99999'));

    $response->assertStatus(404);
});

// 測試未認證取得單一分類
it('未認證使用者取得單一分類會回傳 401 錯誤', function () {
    $category = Category::create([
        'code' => 'C001',
        'name' => '測試分類',
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("categories/{$category->id}"));

    $response->assertStatus(401);
});

// ==================== 更新測試 ====================

// 測試成功更新分類
it('已認證使用者可以更新分類', function () {
    actingAsAuthenticatedUser();

    $category = Category::create([
        'code' => 'C001',
        'name' => '舊名稱',
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("categories/{$category->id}"), [
        'name' => '新名稱',
        'sort_order' => 10,
        'status' => 'INACTIVE',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', '新名稱')
        ->assertJsonPath('data.sort_order', 10)
        ->assertJsonPath('data.status', 'INACTIVE');

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => '新名稱',
        'status' => 'INACTIVE',
    ]);
});

// 測試更新分類驗證錯誤
it('更新分類時驗證錯誤會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $category = Category::create([
        'code' => 'C001',
        'name' => '測試分類',
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("categories/{$category->id}"), [
        'status' => 'INVALID_STATUS',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

// 測試更新不存在的分類
it('更新不存在的分類會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->putJson(apiUrl('categories/99999'), [
        'name' => '新名稱',
    ]);

    $response->assertStatus(404);
});

// 測試未認證更新分類
it('未認證使用者更新分類會回傳 401 錯誤', function () {
    $category = Category::create([
        'code' => 'C001',
        'name' => '測試分類',
        'status' => 'ACTIVE',
    ]);

    $response = $this->putJson(apiUrl("categories/{$category->id}"), [
        'name' => '新名稱',
    ]);

    $response->assertStatus(401);
});

// ==================== 刪除測試 ====================

// 測試成功刪除分類
it('已認證使用者可以刪除沒有子分類和商品的分類', function () {
    actingAsAuthenticatedUser();

    $category = Category::create([
        'code' => 'C001',
        'name' => '測試分類',
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("categories/{$category->id}"));

    $response->assertStatus(200);

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});

// 測試刪除有子分類的分類
it('刪除有子分類的分類會回傳 422 錯誤', function () {
    actingAsAuthenticatedUser();

    $parent = Category::create(['code' => 'C001', 'name' => '父分類', 'status' => 'ACTIVE']);
    Category::create([
        'code' => 'C002',
        'name' => '子分類',
        'parent_id' => $parent->id,
        'level' => 2,
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("categories/{$parent->id}"));

    $response->assertStatus(422);
});

// 測試刪除不存在的分類
it('刪除不存在的分類會回傳 404 錯誤', function () {
    actingAsAuthenticatedUser();

    $response = $this->deleteJson(apiUrl('categories/99999'));

    $response->assertStatus(404);
});

// 測試未認證刪除分類
it('未認證使用者刪除分類會回傳 401 錯誤', function () {
    $category = Category::create([
        'code' => 'C001',
        'name' => '測試分類',
        'status' => 'ACTIVE',
    ]);

    $response = $this->deleteJson(apiUrl("categories/{$category->id}"));

    $response->assertStatus(401);
});

// ==================== 子分類測試 ====================

// 測試取得子分類列表
it('可以取得分類的子分類列表', function () {
    actingAsAuthenticatedUser();

    $parent = Category::create(['code' => 'C001', 'name' => '父分類', 'status' => 'ACTIVE']);
    Category::create([
        'code' => 'C002',
        'name' => '子分類一',
        'parent_id' => $parent->id,
        'level' => 2,
        'status' => 'ACTIVE',
    ]);
    Category::create([
        'code' => 'C003',
        'name' => '子分類二',
        'parent_id' => $parent->id,
        'level' => 2,
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl("categories/{$parent->id}/children"));

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

// ==================== 分類樹測試 ====================

// 測試取得分類樹
it('可以取得完整分類樹結構', function () {
    actingAsAuthenticatedUser();

    $parent = Category::create(['code' => 'C001', 'name' => '父分類', 'status' => 'ACTIVE']);
    Category::create([
        'code' => 'C002',
        'name' => '子分類',
        'parent_id' => $parent->id,
        'level' => 2,
        'status' => 'ACTIVE',
    ]);

    $response = $this->getJson(apiUrl('categories/tree'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'code',
                    'name',
                    'children',
                ],
            ],
        ]);
});
