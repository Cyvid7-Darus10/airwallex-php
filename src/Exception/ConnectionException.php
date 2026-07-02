<?php

declare(strict_types=1);

namespace Airwallex\Exception;

/**
 * The request never received a valid response (network failure, timeout).
 */
class ConnectionException extends AirwallexException
{
}
