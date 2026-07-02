<?php

declare(strict_types=1);

namespace Airwallex\Exception;

/**
 * 429 — too many requests; retry after backing off.
 */
class RateLimitException extends ApiException
{
}
