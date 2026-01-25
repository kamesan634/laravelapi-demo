<?php

namespace App\Services;

use App\Models\NumberSequence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 編號規則服務
 *
 * 提供自動編號生成功能
 */
class NumberSequenceService
{
    /**
     * 取得下一個編號
     *
     * @param  string  $type  編號類型
     *
     * @throws \Exception
     */
    public function getNextNumber(string $type): string
    {
        return DB::transaction(function () use ($type) {
            $sequence = NumberSequence::where('type', $type)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                throw new \Exception("編號規則 {$type} 不存在或未啟用");
            }

            // 檢查是否需要重置
            $this->checkAndReset($sequence);

            // 增加編號
            $sequence->current_number++;
            $sequence->save();

            return $this->formatNumber($sequence);
        });
    }

    /**
     * 預覽下一個編號（不增加）
     *
     * @param  string  $type  編號類型
     */
    public function previewNextNumber(string $type): ?string
    {
        $sequence = NumberSequence::where('type', $type)
            ->where('is_active', true)
            ->first();

        if (! $sequence) {
            return null;
        }

        // 建立副本來計算
        $preview = clone $sequence;
        $this->checkAndReset($preview);
        $preview->current_number++;

        return $this->formatNumber($preview);
    }

    /**
     * 檢查並重置編號
     */
    protected function checkAndReset(NumberSequence $sequence): void
    {
        if ($sequence->reset_period === 'never') {
            return;
        }

        $now = Carbon::now();
        $lastReset = $sequence->last_reset_at ? Carbon::parse($sequence->last_reset_at) : null;

        $shouldReset = match ($sequence->reset_period) {
            'daily' => ! $lastReset || ! $now->isSameDay($lastReset),
            'monthly' => ! $lastReset || ! $now->isSameMonth($lastReset),
            'yearly' => ! $lastReset || ! $now->isSameYear($lastReset),
            default => false,
        };

        if ($shouldReset) {
            $sequence->current_number = 0;
            $sequence->last_reset_at = $now;
        }
    }

    /**
     * 格式化編號
     */
    protected function formatNumber(NumberSequence $sequence): string
    {
        $number = str_pad($sequence->current_number, $sequence->padding, '0', STR_PAD_LEFT);

        $prefix = $sequence->prefix ?? '';
        $suffix = $sequence->suffix ?? '';

        // 支援日期格式化
        $prefix = $this->replaceDatePlaceholders($prefix);
        $suffix = $this->replaceDatePlaceholders($suffix);

        return $prefix.$number.$suffix;
    }

    /**
     * 替換日期佔位符
     */
    protected function replaceDatePlaceholders(string $text): string
    {
        $now = Carbon::now();

        $replacements = [
            '{YYYY}' => $now->format('Y'),
            '{YY}' => $now->format('y'),
            '{MM}' => $now->format('m'),
            '{DD}' => $now->format('d'),
            '{YYYYMM}' => $now->format('Ym'),
            '{YYYYMMDD}' => $now->format('Ymd'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * 初始化預設編號規則
     */
    public function initializeDefaults(): void
    {
        $defaults = [
            [
                'type' => 'ORDER',
                'prefix' => 'SO{YYYYMMDD}',
                'suffix' => '',
                'current_number' => 0,
                'padding' => 4,
                'reset_period' => 'daily',
            ],
            [
                'type' => 'PURCHASE_ORDER',
                'prefix' => 'PO{YYYYMMDD}',
                'suffix' => '',
                'current_number' => 0,
                'padding' => 4,
                'reset_period' => 'daily',
            ],
            [
                'type' => 'GOODS_RECEIPT',
                'prefix' => 'GR{YYYYMMDD}',
                'suffix' => '',
                'current_number' => 0,
                'padding' => 4,
                'reset_period' => 'daily',
            ],
            [
                'type' => 'GOODS_ISSUE',
                'prefix' => 'GI{YYYYMMDD}',
                'suffix' => '',
                'current_number' => 0,
                'padding' => 4,
                'reset_period' => 'daily',
            ],
            [
                'type' => 'STOCK_TRANSFER',
                'prefix' => 'ST{YYYYMMDD}',
                'suffix' => '',
                'current_number' => 0,
                'padding' => 4,
                'reset_period' => 'daily',
            ],
            [
                'type' => 'REFUND',
                'prefix' => 'RF{YYYYMMDD}',
                'suffix' => '',
                'current_number' => 0,
                'padding' => 4,
                'reset_period' => 'daily',
            ],
            [
                'type' => 'MEMBER',
                'prefix' => 'M',
                'suffix' => '',
                'current_number' => 0,
                'padding' => 8,
                'reset_period' => 'never',
            ],
            [
                'type' => 'INVOICE',
                'prefix' => 'INV{YYYYMM}',
                'suffix' => '',
                'current_number' => 0,
                'padding' => 6,
                'reset_period' => 'monthly',
            ],
        ];

        foreach ($defaults as $default) {
            NumberSequence::updateOrCreate(
                ['type' => $default['type']],
                $default
            );
        }
    }
}
