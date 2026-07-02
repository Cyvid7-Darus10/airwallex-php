<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A webhook subscription (where Airwallex sends event notifications).
 *
 * @property-read string|null $id
 * @property-read string|null $request_id
 * @property-read string|null $url
 * @property-read string|null $secret
 * @property-read string|null $version
 * @property-read list<string>|null $events
 * @property-read string|null $status
 * @property-read string|null $created_at
 * @property-read string|null $updated_at
 */
final class WebhookEndpoint extends AirwallexObject
{
}
