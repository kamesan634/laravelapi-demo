<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SystemSetting\BatchUpdateSystemSettingRequest;
use App\Http\Requests\SystemSetting\UpdateSystemSettingRequest;
use App\Models\SystemSetting;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 系統設定控制器
 *
 * 處理系統設定的查詢與更新
 */
class SystemSettingController extends Controller
{
    use ApiResponse;

    /**
     * 取得所有設定
     */
    public function index(Request $request): JsonResponse
    {
        $query = SystemSetting::query();

        // 群組篩選
        if ($request->filled('group')) {
            $query->where('group', $request->group);
        }

        $settings = $query->orderBy('group')->orderBy('key')->get();

        // 依群組分組
        $grouped = $settings->groupBy('group')->map(function ($items, $group) {
            return [
                'group' => $group,
                'settings' => $items->map(function ($setting) {
                    return [
                        'id' => $setting->id,
                        'key' => $setting->key,
                        'value' => $setting->typed_value,
                        'type' => $setting->type,
                        'description' => $setting->description,
                    ];
                })->values(),
            ];
        })->values();

        return $this->success([
            'settings' => $settings->mapWithKeys(fn ($s) => [$s->key => $s->typed_value]),
            'grouped' => $grouped,
            'groups' => $settings->pluck('group')->unique()->values(),
        ], '查詢系統設定成功');
    }

    /**
     * 取得單一設定
     */
    public function show(string $key): JsonResponse
    {
        $setting = SystemSetting::where('key', $key)->first();

        if (! $setting) {
            return $this->notFound('設定不存在');
        }

        return $this->success([
            'key' => $setting->key,
            'value' => $setting->typed_value,
            'type' => $setting->type,
            'group' => $setting->group,
            'description' => $setting->description,
        ], '取得設定成功');
    }

    /**
     * 更新設定
     */
    public function update(UpdateSystemSettingRequest $request, string $key): JsonResponse
    {
        $setting = SystemSetting::where('key', $key)->first();

        if (! $setting) {
            return $this->notFound('設定不存在');
        }

        try {
            $setting->typed_value = $request->input('value');
            $setting->save();

            return $this->success([
                'key' => $setting->key,
                'value' => $setting->typed_value,
            ], '設定更新成功');
        } catch (\Exception $e) {
            return $this->serverError('設定更新失敗：'.$e->getMessage());
        }
    }

    /**
     * 批次更新設定
     */
    public function batch(BatchUpdateSystemSettingRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $updated = [];
            foreach ($request->settings as $item) {
                $setting = SystemSetting::where('key', $item['key'])->first();
                if ($setting) {
                    $setting->typed_value = $item['value'];
                    $setting->save();
                    $updated[$setting->key] = $setting->typed_value;
                }
            }

            DB::commit();

            return $this->success([
                'updated' => $updated,
            ], '批次更新設定成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('批次更新設定失敗：'.$e->getMessage());
        }
    }
}
