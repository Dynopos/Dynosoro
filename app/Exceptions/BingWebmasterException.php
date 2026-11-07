<?php

namespace App\Exceptions;

use RuntimeException;

/** A Bing Webmaster API failure, with a message in BM the admin can act on. */
class BingWebmasterException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 0)
    {
        parent::__construct($message);
    }
}
