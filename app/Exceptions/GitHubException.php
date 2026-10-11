<?php

namespace App\Exceptions;

use RuntimeException;

class GitHubException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 0)
    {
        parent::__construct($message);
    }
}
