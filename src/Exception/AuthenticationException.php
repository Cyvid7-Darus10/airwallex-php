<?php

declare(strict_types=1);

namespace Airwallex\Exception;

/**
 * 401 — credentials are missing, invalid, or the token expired.
 */
class AuthenticationException extends ApiException
{
}
