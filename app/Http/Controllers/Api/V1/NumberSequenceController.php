<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\NumberSequence\StoreNumberSequenceRequest;
use App\Models\NumberSequence;
use App\Services\NumberSequenceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 編號規則控制器
 *
 * 處理編號規則的 CRUD 操作
 */
class NumberSequenceController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected NumberSequenceService $numberSequenceService
    ) {}

    /**
     * 取得編號規則列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = NumberSequence::query();

        // 啟用狀態篩選
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // 排序
        $query->orderBy('type');

        $sequences = $query->get();

        return $this->success($sequences, '查詢編號規則列表成功');
    }

    /**
     * 新增編號規則
     */
    public function store(StoreNumberSequenceRequest $request): JsonResponse
    {
        try {
            $sequence = NumberSequence::create($request->validated());

            return $this->created($sequence, '編號規則建立成功');
        } catch (\Exception $e) {
            return $this->serverError('編號規則建立失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得編號規則詳情
     */
    public function show(NumberSequence $numberSequence): JsonResponse
    {
        // 預覽下一個編號
        $nextNumber = $this->numberSequenceService->previewNextNumber($numberSequence->type);

        return $this->success([
            'sequence' => $numberSequence,
            'next_number_preview' => $nextNumber,
        ], '取得編號規則詳情成功');
    }

    /**
     * 更新編號規則
     */
    public function update(Request $request, NumberSequence $numberSequence): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'string', 'max:50', Rule::unique('number_sequences', 'type')->ignore($numberSequence->id), 'regex:/^[A-Z_]+$/'],
            'prefix' => ['nullable', 'string', 'max:20'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'current_number' => ['integer', 'min:0'],
            'padding' => ['integer', 'min:1', 'max:10'],
            'reset_period' => ['in:never,daily,monthly,yearly'],
            'is_active' => ['boolean'],
        ]);

        try {
            $numberSequence->update($validated);

            return $this->success($numberSequence, '編號規則更新成功');
        } catch (\Exception $e) {
            return $this->serverError('編號規則更新失敗：'.$e->getMessage());
        }
    }

    /**
     * 刪除編號規則
     */
    public function destroy(NumberSequence $numberSequence): JsonResponse
    {
        try {
            $numberSequence->delete();

            return $this->success(null, '編號規則刪除成功');
        } catch (\Exception $e) {
            return $this->serverError('編號規則刪除失敗：'.$e->getMessage());
        }
    }

    /**
     * 取得下一個編號
     */
    public function next(NumberSequence $numberSequence): JsonResponse
    {
        try {
            $nextNumber = $this->numberSequenceService->getNextNumber($numberSequence->type);

            return $this->success([
                'number' => $nextNumber,
            ], '取得下一個編號成功');
        } catch (\Exception $e) {
            return $this->serverError('取得下一個編號失敗：'.$e->getMessage());
        }
    }
}
