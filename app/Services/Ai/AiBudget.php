<?php

namespace App\Services\Ai;

use App\Exceptions\BudgetExceeded;
use App\Models\AiUsage;

/**
 * Platform AI limits (spec §8.4), checked before every Vertex call:
 *  - AI_MONTHLY_BUDGET_MYR: estimated cost this month, all websites together,
 *  - MAX_DAILY_AI_CALLS: number of calls today, all websites together.
 * Both protect Bob's GCP credit, so they count every workspace.
 */
class AiBudget
{
    public function limit(): float
    {
        return (float) config('dynoseo.ai.monthly_budget_myr');
    }

    public function dailyCallLimit(): int
    {
        return (int) config('dynoseo.ai.max_daily_calls');
    }

    public function spentThisMonth(): float
    {
        return (float) AiUsage::query()->withoutGlobalScope('workspace')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('cost_estimate');
    }

    public function callsToday(): int
    {
        return AiUsage::query()->withoutGlobalScope('workspace')
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    public function remaining(): float
    {
        return max(0, $this->limit() - $this->spentThisMonth());
    }

    public function isExhausted(): bool
    {
        return $this->spentThisMonth() >= $this->limit() || $this->callsToday() >= $this->dailyCallLimit();
    }

    /** @throws BudgetExceeded */
    public function assertCanSpend(float $estimateMyr): void
    {
        if ($this->callsToday() >= $this->dailyCallLimit()) {
            throw new BudgetExceeded(BudgetExceeded::DAILY);
        }

        if ($this->spentThisMonth() + max(0, $estimateMyr) > $this->limit()) {
            throw new BudgetExceeded;
        }
    }
}
