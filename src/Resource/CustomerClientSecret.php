<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A short-lived client secret for customer-scoped calls.
 *
 * @property-read string|null $client_secret
 * @property-read string|null $expired_time
 */
final class CustomerClientSecret extends AirwallexObject
{
}
