<?php

namespace App\Exceptions;

use RuntimeException;

/** Thrown before a Vertex call that would push AI cost or call count over the limit. */
class BudgetExceeded extends RuntimeException
{
    public const MESSAGE = 'Had kos AI bulan ini dah dicapai. Naikkan AI_MONTHLY_BUDGET_MYR atau tunggu bulan depan.';

    public const DAILY = 'Had panggilan AI hari ini dah dicapai (MAX_DAILY_AI_CALLS). Sambung esok.';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct($message);
    }
}
