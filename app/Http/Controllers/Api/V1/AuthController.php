<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * 認證控制器
 *
 * 處理使用者登入、註冊、登出等認證相關功能
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * 使用者註冊
     *
     * @param  RegisterRequest  $request  註冊請求
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // 建立使用者
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 建立 API Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->created([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], '註冊成功');
    }

    /**
     * 使用者登入
     *
     * @param  LoginRequest  $request  登入請求
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // 查詢使用者
        $user = User::where('email', $request->email)->first();

        // 驗證密碼
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->unauthorized('帳號或密碼錯誤');
        }

        // 刪除舊的 Token（可選，依需求決定是否允許多裝置登入）
        // $user->tokens()->delete();

        // 建立新的 API Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], '登入成功');
    }

    /**
     * 使用者登出
     */
    public function logout(Request $request): JsonResponse
    {
        // 刪除目前使用的 Token
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, '登出成功');
    }

    /**
     * 取得目前登入的使用者資訊
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ], '取得使用者資訊成功');
    }

    /**
     * 更新密碼
     *
     * @param  UpdatePasswordRequest  $request  更新密碼請求
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        // 更新密碼
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // 刪除所有 Token，強制重新登入
        $user->tokens()->delete();

        // 建立新的 Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
        ], '密碼更新成功，請使用新密碼登入');
    }
}
