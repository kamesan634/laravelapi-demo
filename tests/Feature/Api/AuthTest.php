<?php

/**
 * 認證 API 測試
 *
 * 測試使用者註冊、登入、登出、取得使用者資訊等功能
 */

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// ==================== 註冊測試 ====================

// 測試成功註冊
it('可以成功註冊新使用者', function () {
    $userData = [
        'name' => '測試使用者',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->postJson(apiUrl('auth/register'), $userData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email', 'created_at'],
                'token',
                'token_type',
            ],
        ]);

    // 確認使用者已建立
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'name' => '測試使用者',
    ]);
});

// 測試註冊驗證錯誤 - 缺少必填欄位
it('註冊時缺少必填欄位會回傳 422 錯誤', function () {
    $response = $this->postJson(apiUrl('auth/register'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

// 測試註冊驗證錯誤 - Email 格式錯誤
it('註冊時 Email 格式錯誤會回傳 422 錯誤', function () {
    $userData = [
        'name' => '測試使用者',
        'email' => 'invalid-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->postJson(apiUrl('auth/register'), $userData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

// 測試註冊驗證錯誤 - Email 已存在
it('註冊時 Email 已存在會回傳 422 錯誤', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $userData = [
        'name' => '測試使用者',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->postJson(apiUrl('auth/register'), $userData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

// 測試註冊驗證錯誤 - 密碼確認不一致
it('註冊時密碼確認不一致會回傳 422 錯誤', function () {
    $userData = [
        'name' => '測試使用者',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different_password',
    ];

    $response = $this->postJson(apiUrl('auth/register'), $userData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

// ==================== 登入測試 ====================

// 測試成功登入
it('可以成功登入', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson(apiUrl('auth/login'), [
        'email' => 'login@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email', 'created_at'],
                'token',
                'token_type',
            ],
        ]);
});

// 測試登入驗證錯誤 - 帳號或密碼錯誤
it('登入時帳號或密碼錯誤會回傳 401 錯誤', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('correct_password'),
    ]);

    $response = $this->postJson(apiUrl('auth/login'), [
        'email' => 'user@example.com',
        'password' => 'wrong_password',
    ]);

    $response->assertStatus(401);
});

// 測試登入驗證錯誤 - 缺少必填欄位
it('登入時缺少必填欄位會回傳 422 錯誤', function () {
    $response = $this->postJson(apiUrl('auth/login'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

// 測試登入驗證錯誤 - 使用者不存在
it('登入時使用者不存在會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl('auth/login'), [
        'email' => 'nonexistent@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(401);
});

// ==================== 登出測試 ====================

// 測試成功登出
it('已認證使用者可以成功登出', function () {
    actingAsAuthenticatedUser();

    $response = $this->postJson(apiUrl('auth/logout'));

    $response->assertStatus(200);
});

// 測試未認證登出
it('未認證使用者登出會回傳 401 錯誤', function () {
    $response = $this->postJson(apiUrl('auth/logout'));

    $response->assertStatus(401);
});

// ==================== 取得使用者資訊測試 ====================

// 測試成功取得使用者資訊
it('已認證使用者可以取得自己的資訊', function () {
    $user = actingAsAuthenticatedUser();

    $response = $this->getJson(apiUrl('auth/me'));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'name',
                'email',
                'email_verified_at',
                'created_at',
                'updated_at',
            ],
        ])
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

// 測試未認證取得使用者資訊
it('未認證使用者取得資訊會回傳 401 錯誤', function () {
    $response = $this->getJson(apiUrl('auth/me'));

    $response->assertStatus(401);
});

// ==================== 更新密碼測試 ====================

// 測試成功更新密碼
it('已認證使用者可以成功更新密碼', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old_password'),
    ]);
    $this->actingAs($user, 'sanctum');

    $response = $this->putJson(apiUrl('auth/password'), [
        'current_password' => 'old_password',
        'password' => 'new_password123',
        'password_confirmation' => 'new_password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'token_type',
            ],
        ]);

    // 驗證新密碼可以登入
    $this->assertTrue(Hash::check('new_password123', $user->fresh()->password));
});

// 測試更新密碼驗證錯誤 - 當前密碼錯誤
it('更新密碼時當前密碼錯誤會回傳 422 錯誤', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correct_password'),
    ]);
    $this->actingAs($user, 'sanctum');

    $response = $this->putJson(apiUrl('auth/password'), [
        'current_password' => 'wrong_password',
        'password' => 'new_password123',
        'password_confirmation' => 'new_password123',
    ]);

    $response->assertStatus(422);
});

// 測試未認證更新密碼
it('未認證使用者更新密碼會回傳 401 錯誤', function () {
    $response = $this->putJson(apiUrl('auth/password'), [
        'current_password' => 'old_password',
        'password' => 'new_password123',
        'password_confirmation' => 'new_password123',
    ]);

    $response->assertStatus(401);
});
