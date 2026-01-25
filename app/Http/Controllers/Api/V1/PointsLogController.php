<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PointsLog\StorePointsLogRequest;
use App\Models\Customer;
use App\Models\PointsLog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 點數紀錄控制器
 *
 * 處理會員點數的查詢與調整操作
 */
class PointsLogController extends Controller
{
    use ApiResponse;

    /**
     * 查詢會員點數紀錄
     *
     * @param  Customer  $customer  會員
     */
    public function index(Request $request, Customer $customer): JsonResponse
    {
        $query = PointsLog::where('customer_id', $customer->id)
            ->with('creator');

        // 異動類型篩選
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // 日期範圍篩選
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // 排序（預設依建立時間降冪）
        $query->orderBy('created_at', 'desc');

        // 分頁
        $perPage = $request->input('per_page', 15);
        $pointsLogs = $query->paginate($perPage);

        return $this->paginated($pointsLogs, '查詢點數紀錄成功');
    }

    /**
     * 手動調整點數
     *
     * @param  StorePointsLogRequest  $request  調整請求
     * @param  Customer  $customer  會員
     */
    public function store(StorePointsLogRequest $request, Customer $customer): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $points = (int) $data['points'];
            $type = $data['type'];

            // 計算點數變動前後
            $beforeBalance = $customer->available_points;

            // 根據類型判斷點數加減
            // EARN: 獲得點數（正數）
            // REDEEM: 兌換扣點（負數）
            // ADJUST: 手動調整（可正可負）
            // EXPIRE: 過期扣點（負數）
            // BONUS: 獎勵點數（正數）
            // REFUND: 退貨扣點（負數）
            if (in_array($type, ['REDEEM', 'EXPIRE', 'REFUND'])) {
                // 這些類型一定是扣點，確保是負數
                $points = -abs($points);
            } elseif (in_array($type, ['EARN', 'BONUS'])) {
                // 這些類型一定是加點，確保是正數
                $points = abs($points);
            }
            // ADJUST 類型保持原本的正負值

            $afterBalance = $beforeBalance + $points;

            // 檢查點數是否足夠（不允許負數餘額）
            if ($afterBalance < 0) {
                return $this->error('會員點數不足，無法完成此操作', 422);
            }

            // 建立點數紀錄
            $pointsLog = PointsLog::create([
                'customer_id' => $customer->id,
                'type' => $type,
                'points' => $points,
                'balance' => $afterBalance,
                'reference_type' => 'MANUAL',
                'reference_id' => null,
                'description' => $data['description'] ?? '手動調整點數',
                'expire_date' => $data['expire_date'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // 更新會員點數
            $customer->available_points = $afterBalance;
            if ($points > 0) {
                $customer->total_points += $points;
            }
            $customer->save();

            DB::commit();

            $pointsLog->load('creator');

            return $this->created($pointsLog, '點數調整成功');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('點數調整失敗：'.$e->getMessage());
        }
    }
}
