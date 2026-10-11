<?php

namespace App\Exceptions;

use RuntimeException;

/** No draft passed the checks within the rewrite limit. */
class ArticleRejected extends RuntimeException
{
    public function __construct(string $message, public readonly array $issues = [])
    {
        parent::__construct($message);
    }
}
