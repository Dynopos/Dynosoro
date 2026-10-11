<?php

namespace App\Exceptions;

use RuntimeException;

/** A failed Vertex AI call. Billing failures mean the GCP credit is used up or billing is off. */
class VertexException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $billing = false, public readonly int $status = 0)
    {
        parent::__construct($message);
    }
}
