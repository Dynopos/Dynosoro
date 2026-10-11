<?php

namespace App\Exceptions;

use RuntimeException;

/** The app tried to write a file outside the Dyno SEO paths of a website repo (rule 5). */
class PublishRuleViolation extends RuntimeException {}
