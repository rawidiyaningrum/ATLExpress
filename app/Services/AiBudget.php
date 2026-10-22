<?php

namespace App\Services;

use App\Models\AiUsage;
use Illuminate\Support\Carbon;

/**
 * Pengaman biaya: membatasi pengeluaran AI per bulan dan per batch.
 */
class AiBudget
{
    public function monthlyCap(): float
    {
        return (float) config('ai.budget.monthly_cap_usd', 0);
    }

    public function runCap(): float
    {
        return (float) config('ai.budget.run_cap_usd', 0);
    }

    public function spent(?Carbon $month = null): float
    {
        $month ??= now();

        return (float) AiUsage::query()
            ->where('status', 'success')
            ->whereBetween('created_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth(),
            ])
            ->sum('estimated_cost_usd');
    }

    public function remaining(): float
    {
        if ($this->monthlyCap() <= 0) {
            return 0.0;
        }

        return max(0.0, round($this->monthlyCap() - $this->spent(), 6));
    }

    public function isEnabled(): bool
    {
        return $this->monthlyCap() > 0;
    }

    public function canAfford(float $estimatedCost): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        return $this->remaining() >= $estimatedCost;
    }

    /**
     * Cek cepat sebelum satu pemanggilan AI (dipakai per artikel di dalam job).
     */
    public function canAffordCost(float $estimatedCost): bool
    {
        return $this->canAfford($estimatedCost);
    }

    /**
     * @return array{allowed: bool, reason: string, estimated: float, remaining: float}
     */
    public function check(float $estimatedCost): array
    {
        $remaining = $this->remaining();

        if (! $this->isEnabled()) {
            return [
                'allowed' => false,
                'reason' => 'Cap biaya bulanan AI adalah 0 USD, pemanggilan AI dinonaktifkan.',
                'estimated' => $estimatedCost,
                'remaining' => 0.0,
            ];
        }

        if ($estimatedCost > $this->runCap() && $this->runCap() > 0) {
            return [
                'allowed' => false,
                'reason' => 'Perkiraan biaya batch melebihi cap per batch (USD '.number_format($this->runCap(), 2).'). Kurangi jumlah artikel.',
                'estimated' => $estimatedCost,
                'remaining' => $remaining,
            ];
        }

        if ($estimatedCost > $remaining) {
            return [
                'allowed' => false,
                'reason' => 'Sisa budget bulan ini (USD '.number_format($remaining, 2).') tidak cukup untuk perkiraan biaya batch (USD '.number_format($estimatedCost, 2).').',
                'estimated' => $estimatedCost,
                'remaining' => $remaining,
            ];
        }

        return [
            'allowed' => true,
            'reason' => '',
            'estimated' => $estimatedCost,
            'remaining' => $remaining,
        ];
    }
}
