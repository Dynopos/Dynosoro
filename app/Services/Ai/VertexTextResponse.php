<?php

namespace App\Services\Ai;

final class VertexTextResponse
{
    public function __construct(
        public readonly string $model,
        public readonly string $text,
        public readonly ?string $finishReason,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
    ) {}

    /** Decoded JSON body, or null when the model did not return valid JSON. */
    public function json(): ?array
    {
        $text = trim($this->text);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text) ?? $text;
        $data = json_decode($text, true);

        return is_array($data) ? $data : null;
    }
}
