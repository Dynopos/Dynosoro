<?php

namespace App\Services\Ai;

use App\Exceptions\PricesNotConfigured;
use App\Models\AiUsage;
use App\Services\Costs\PriceTable;

/**
 * Every Vertex AI call goes through here (rule 4):
 *   1. price check (no price → no call),
 *   2. AiBudget::assertCanSpend() with a worst-case estimate,
 *   3. the call,
 *   4. an ai_usage row with real token counts and cost.
 */
class AiGateway
{
    public function __construct(
        private VertexClient $client,
        private AiBudget $budget,
        private PriceTable $prices,
    ) {}

    public function text(
        string $purpose,
        string $model,
        string $system,
        string $user,
        int $maxTokens,
        ?array $schema = null,
        ?int $thinkingBudget = null,
        float $temperature = 0.7,
        ?int $siteId = null,
        ?int $articleId = null,
    ): VertexTextResponse {
        if ($model === '') {
            throw PricesNotConfigured::missingModel('GEMINI_MODEL_'.strtoupper($purpose === 'write' ? 'write' : 'check'));
        }

        // Worst case: ~3 characters per input token, and the full output allowance.
        $estimatedInput = (int) ceil((mb_strlen($system) + mb_strlen($user)) / 3);
        $this->budget->assertCanSpend($this->prices->textCostMyr($model, $estimatedInput, $maxTokens));

        $response = $this->client->generate($model, $system, $user, $maxTokens, $schema, $thinkingBudget, $temperature);

        AiUsage::query()->create([
            'site_id' => $siteId,
            'article_id' => $articleId,
            'model' => $model,
            'purpose' => $purpose,
            'input_tokens' => $response->inputTokens,
            'output_tokens' => $response->outputTokens,
            'cost_estimate' => $this->prices->textCostMyr($model, $response->inputTokens, $response->outputTokens),
        ]);

        return $response;
    }

    public function image(string $model, string $prompt, ?int $siteId = null, ?int $articleId = null): VertexImageResponse
    {
        if ($model === '') {
            throw PricesNotConfigured::missingModel('IMAGEN_MODEL');
        }

        $cost = $this->prices->imageCostMyr($model);
        $this->budget->assertCanSpend($cost);

        $response = $this->client->image($model, $prompt);

        AiUsage::query()->create([
            'site_id' => $siteId,
            'article_id' => $articleId,
            'model' => $model,
            'purpose' => 'image',
            // Imagen bills per generated image; a filtered prompt returns none.
            'images' => $response->bytes !== null ? 1 : 0,
            'cost_estimate' => $response->bytes !== null ? $cost : 0,
        ]);

        return $response;
    }
}
