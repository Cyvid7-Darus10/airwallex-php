<?php

declare(strict_types=1);

namespace Airwallex;

/**
 * A parsed webhook notification.
 *
 * `data` holds the resource payload; its exact shape depends on `name` —
 * see the Airwallex event types documentation.
 *
 * @property-read string|null $id
 * @property-read string|null $name
 * @property-read string|null $account_id
 * @property-read array<string, mixed>|null $data
 * @property-read string|null $created_at
 */
final class WebhookEvent extends AirwallexObject
{
}
