<?php

declare(strict_types=1);

namespace Airwallex\Exception;

/**
 * 409 — the request conflicts with the current resource state (e.g. a duplicate request_id); never retried automatically.
 */
class ConflictException extends ApiException
{
}
