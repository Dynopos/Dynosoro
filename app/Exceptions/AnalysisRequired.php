<?php

namespace App\Exceptions;

use RuntimeException;

/** Topics are only planned from a business analysis the admin has confirmed. */
class AnalysisRequired extends RuntimeException
{
    public const MESSAGE = 'Sahkan analisis bisnes website ini dahulu (skrin Website). Topik hanya dirancang selepas bidang bisnes disahkan.';

    public function __construct(string $message = self::MESSAGE)
    {
        parent::__construct($message);
    }
}
