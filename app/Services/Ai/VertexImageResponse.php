<?php

namespace App\Services\Ai;

final class VertexImageResponse
{
    public function __construct(
        public readonly string $model,
        public readonly ?string $bytes,
        public readonly string $mimeType,
        public readonly ?string $filteredReason = null,
    ) {}
}
