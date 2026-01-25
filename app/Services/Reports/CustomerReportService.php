<?php

namespace App\Services\Reports;

use App\Models\Customer;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 客戶報表服務
 *
 * 提供客戶相關的報表數據
 */
class CustomerReportService
{
    /**
     * 取得客戶報表
     */
    public function getCustomerReport(array $filters): array
    {
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::now()->startOfMonth();
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now();

        // 客戶統計
        $totalCustomers = Customer::where('status', 'ACTIVE')->count();
        $newCustomers = Customer::whereBetween('created_at', [$startDate, $endDate])->count();

        // 活躍客戶（期間內有訂單）
        $activeCustomers = Order::whereBetween('order_date', [$startDate, $endDate])
            ->whereNotNull('customer_id')
            ->whereIn('status', ['COMPLETED', 'PAID'])
            ->distinct('customer_id')
            ->count('customer_id');

        // 客戶消費統計
        $spendingStats = Order::whereBetween('order_date', [$startDate, $endDate])
            ->whereNotNull('customer_id')
            ->whereIn('status', ['COMPLETED', 'PAID'])
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(total_amount) as total_spending,
                AVG(total_amount) as average_order_value,
                COUNT(DISTINCT customer_id) as unique_customers
            ')
            ->first();

        // 依會員等級統計
        $byLevel = DB::table('customers')
            ->join('customer_levels', 'customers.level_id', '=', 'customer_levels.id')
            ->where('customers.status', 'ACTIVE')
            ->selectRaw('
                customer_levels.id,
                customer_levels.name,
                COUNT(customers.id) as customer_count,
                SUM(customers.total_spending) as total_spending
            ')
            ->groupBy('customer_levels.id', 'customer_levels.name')
            ->orderBy('customer_levels.id')
            ->get();

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_customers' => $totalCustomers,
                'new_customers' => $newCustomers,
                'active_customers' => $activeCustomers,
                'total_orders' => $spendingStats->total_orders ?? 0,
                'total_spending' => (float) ($spendingStats->total_spending ?? 0),
                'average_order_value' => round($spendingStats->average_order_value ?? 0, 2),
            ],
            'by_level' => $byLevel->map(function ($item) use ($totalCustomers) {
                return [
                    'level_id' => $item->id,
                    'name' => $item->name,
                    'customer_count' => (int) $item->customer_count,
                    'total_spending' => (float) $item->total_spending,
                    'percentage' => $totalCustomers > 0
                        ? round(($item->customer_count / $totalCustomers) * 100, 2)
                        : 0,
                ];
            })->toArray(),
        ];
    }

    /**
     * RFM 分析
     */
    public function getRfmAnalysis(array $filters): array
    {
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now();
        $months = $filters['months'] ?? 12;
        $startDate = $endDate->copy()->subMonths($months);

        // 計算每個客戶的 RFM 指標
        $customers = DB::table('customers')
            ->leftJoin('orders', function ($join) use ($startDate, $endDate) {
                $join->on('customers.id', '=', 'orders.customer_id')
                    ->whereBetween('orders.order_date', [$startDate, $endDate])
                    ->whereIn('orders.status', ['COMPLETED', 'PAID']);
            })
            ->where('customers.status', 'ACTIVE')
            ->selectRaw('
                customers.id,
                customers.name,
                customers.member_no,
                MAX(orders.order_date) as last_order_date,
                COUNT(orders.id) as order_count,
                COALESCE(SUM(orders.total_amount), 0) as total_spending
            ')
            ->groupBy('customers.id', 'customers.name', 'customers.member_no')
            ->get();

        // 計算 RFM 分數
        $maxRecency = $customers->max(function ($c) use ($endDate) {
            return $c->last_order_date
                ? $endDate->diffInDays(Carbon::parse($c->last_order_date))
                : 365;
        });
        $maxFrequency = $customers->max('order_count') ?: 1;
        $maxMonetary = $customers->max('total_spending') ?: 1;

        $rfmData = $customers->map(function ($customer) use ($endDate, $maxRecency, $maxFrequency, $maxMonetary) {
            $recencyDays = $customer->last_order_date
                ? $endDate->diffInDays(Carbon::parse($customer->last_order_date))
                : 365;

            // 計算 1-5 分數（5 分最好）
            $rScore = $maxRecency > 0 ? 5 - floor(($recencyDays / $maxRecency) * 4) : 1;
            $fScore = $maxFrequency > 0 ? floor(($customer->order_count / $maxFrequency) * 4) + 1 : 1;
            $mScore = $maxMonetary > 0 ? floor(($customer->total_spending / $maxMonetary) * 4) + 1 : 1;

            // 確保分數在 1-5 之間
            $rScore = max(1, min(5, $rScore));
            $fScore = max(1, min(5, $fScore));
            $mScore = max(1, min(5, $mScore));

            // 客戶分群
            $segment = $this->getCustomerSegment($rScore, $fScore, $mScore);

            return [
                'customer_id' => $customer->id,
                'member_no' => $customer->member_no,
                'name' => $customer->name,
                'last_order_date' => $customer->last_order_date,
                'recency_days' => $recencyDays,
                'frequency' => (int) $customer->order_count,
                'monetary' => (float) $customer->total_spending,
                'r_score' => $rScore,
                'f_score' => $fScore,
                'm_score' => $mScore,
                'rfm_score' => $rScore.$fScore.$mScore,
                'segment' => $segment,
            ];
        });

        // 統計各分群人數
        $segments = $rfmData->groupBy('segment')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_spending' => $group->sum('monetary'),
            ];
        });

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'segments' => $segments->toArray(),
            'customers' => $rfmData->sortByDesc('monetary')->take(100)->values()->toArray(),
        ];
    }

    /**
     * 客戶消費排行
     */
    public function getCustomerRanking(array $filters): array
    {
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::now()->startOfYear();
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now();
        $limit = $filters['limit'] ?? 50;

        $customers = DB::table('customers')
            ->join('orders', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('customer_levels', 'customers.level_id', '=', 'customer_levels.id')
            ->whereBetween('orders.order_date', [$startDate, $endDate])
            ->whereIn('orders.status', ['COMPLETED', 'PAID'])
            ->selectRaw('
                customers.id,
                customers.member_no,
                customers.name,
                customers.phone,
                customer_levels.name as level_name,
                COUNT(orders.id) as order_count,
                SUM(orders.total_amount) as total_spending,
                AVG(orders.total_amount) as average_order_value,
                MAX(orders.order_date) as last_order_date
            ')
            ->groupBy('customers.id', 'customers.member_no', 'customers.name', 'customers.phone', 'customer_levels.name')
            ->orderByDesc('total_spending')
            ->limit($limit)
            ->get();

        $totalSpending = $customers->sum('total_spending');

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'total_spending' => (float) $totalSpending,
            'customers' => $customers->map(function ($customer, $index) use ($totalSpending) {
                return [
                    'rank' => $index + 1,
                    'customer_id' => $customer->id,
                    'member_no' => $customer->member_no,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'level_name' => $customer->level_name,
                    'order_count' => (int) $customer->order_count,
                    'total_spending' => (float) $customer->total_spending,
                    'average_order_value' => round($customer->average_order_value, 2),
                    'last_order_date' => $customer->last_order_date,
                    'spending_share' => $totalSpending > 0
                        ? round(($customer->total_spending / $totalSpending) * 100, 2)
                        : 0,
                ];
            })->toArray(),
        ];
    }

    /**
     * 客戶留存分析
     */
    public function getRetention(array $filters): array
    {
        $months = $filters['months'] ?? 6;
        $endDate = Carbon::now()->endOfMonth();
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();

        $retentionData = [];
        $currentMonth = $startDate->copy();

        while ($currentMonth < $endDate) {
            $monthStart = $currentMonth->copy()->startOfMonth();
            $monthEnd = $currentMonth->copy()->endOfMonth();
            $monthKey = $monthStart->format('Y-m');

            // 該月新客戶
            $newCustomers = Customer::whereBetween('created_at', [$monthStart, $monthEnd])
                ->pluck('id');

            $newCount = $newCustomers->count();
            $cohortData = ['month' => $monthKey, 'new_customers' => $newCount, 'retention' => []];

            if ($newCount > 0) {
                // 計算後續每月的留存
                $followupMonth = $currentMonth->copy()->addMonth();
                $monthIndex = 1;

                while ($followupMonth <= $endDate && $monthIndex <= 6) {
                    $followupStart = $followupMonth->copy()->startOfMonth();
                    $followupEnd = $followupMonth->copy()->endOfMonth();

                    $retained = Order::whereIn('customer_id', $newCustomers)
                        ->whereBetween('order_date', [$followupStart, $followupEnd])
                        ->whereIn('status', ['COMPLETED', 'PAID'])
                        ->distinct('customer_id')
                        ->count('customer_id');

                    $cohortData['retention']['month_'.$monthIndex] = [
                        'count' => $retained,
                        'rate' => round(($retained / $newCount) * 100, 1),
                    ];

                    $followupMonth->addMonth();
                    $monthIndex++;
                }
            }

            $retentionData[] = $cohortData;
            $currentMonth->addMonth();
        }

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'cohorts' => $retentionData,
        ];
    }

    /**
     * 根據 RFM 分數判斷客戶分群
     */
    protected function getCustomerSegment(int $r, int $f, int $m): string
    {
        $avg = ($r + $f + $m) / 3;

        if ($r >= 4 && $f >= 4 && $m >= 4) {
            return 'CHAMPIONS'; // 最佳客戶
        }
        if ($r >= 4 && $f >= 3) {
            return 'LOYAL_CUSTOMERS'; // 忠誠客戶
        }
        if ($r >= 4 && $f <= 2 && $m <= 2) {
            return 'NEW_CUSTOMERS'; // 新客戶
        }
        if ($r >= 3 && $f >= 3 && $m >= 3) {
            return 'POTENTIAL_LOYALISTS'; // 潛在忠誠客戶
        }
        if ($r <= 2 && $f >= 4 && $m >= 4) {
            return 'AT_RISK'; // 需要關注的高價值客戶
        }
        if ($r <= 2 && $f <= 2) {
            return 'HIBERNATING'; // 沉睡客戶
        }
        if ($avg >= 3) {
            return 'PROMISING'; // 有潛力的客戶
        }

        return 'NEED_ATTENTION'; // 需要關注
    }

    /**
     * 取得匯出用的客戶數據
     */
    public function getExportData(array $filters): array
    {
        return Customer::with('level')
            ->where('status', 'ACTIVE')
            ->orderBy('total_spending', 'desc')
            ->get()
            ->map(function ($customer) {
                return [
                    'member_no' => $customer->member_no,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'level_name' => $customer->level?->name ?? '',
                    'total_spending' => $customer->total_spending,
                    'total_points' => $customer->total_points,
                    'available_points' => $customer->available_points,
                    'join_date' => $customer->join_date?->format('Y-m-d') ?? '',
                    'status' => $customer->status,
                ];
            })
            ->toArray();
    }
}
