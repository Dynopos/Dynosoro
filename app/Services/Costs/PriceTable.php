<?php

namespace App\Services\Costs;

use App\Exceptions\PricesNotConfigured;

/** Reads prices from config/ai_prices.php (or PRICE_TABLE_PATH). No prices in code. */
class PriceTable
{
    private array $table;

    public function __construct(?array $table = null)
    {
        $this->table = $table ?? $this->load();
    }

    private function load(): array
    {
        $path = (string) config('dynoseo.price_table_path', 'config/ai_prices.php');

        if ($path === '' || $path === 'config/ai_prices.php') {
            return (array) config('ai_prices', []);
        }

        $file = str_starts_with($path, '/') ? $path : base_path($path);

        return is_file($file) ? (array) require $file : (array) config('ai_prices', []);
    }

    public function usdToMyr(): ?float
    {
        $rate = $this->table['usd_to_myr'] ?? null;

        return is_numeric($rate) && $rate > 0 ? (float) $rate : null;
    }

    /** USD per million tokens, or null when not filled in. */
    public function textPrices(string $model): ?array
    {
        $prices = $this->table['text'][$model] ?? null;

        if (! is_array($prices) || ! is_numeric($prices['input'] ?? null) || ! is_numeric($prices['output'] ?? null)) {
            return null;
        }

        return ['input' => (float) $prices['input'], 'output' => (float) $prices['output']];
    }

    public function imagePrice(string $model): ?float
    {
        $usd = $this->table['image'][$model] ?? null;

        return is_numeric($usd) ? (float) $usd : null;
    }

    /** @throws PricesNotConfigured */
    public function textCostMyr(string $model, int $input, int $output): float
    {
        $prices = $this->textPrices($model) ?? throw PricesNotConfigured::forModel($model);
        $rate = $this->usdToMyr() ?? throw PricesNotConfigured::forCurrency();

        return round(($input * $prices['input'] + $output * $prices['output']) / 1_000_000 * $rate, 6);
    }

    /** @throws PricesNotConfigured */
    public function imageCostMyr(string $model, int $images = 1): float
    {
        $usd = $this->imagePrice($model) ?? throw PricesNotConfigured::forModel($model);
        $rate = $this->usdToMyr() ?? throw PricesNotConfigured::forCurrency();

        return round($usd * $images * $rate, 6);
    }
}
