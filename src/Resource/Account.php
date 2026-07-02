<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * Details of the Airwallex account the API credentials belong to.
 *
 * @property-read string|null $id
 * @property-read string|null $identifier
 * @property-read string|null $nickname
 * @property-read string|null $status
 * @property-read string|null $view_type
 * @property-read array<string, mixed>|null $account_details
 * @property-read array<string, mixed>|null $primary_contact
 * @property-read array<string, mixed>|null $reactivate_details
 * @property-read list<array<string, mixed>>|null $suspend_details
 * @property-read array<string, mixed>|null $metadata
 * @property-read string|null $created_at
 */
final class Account extends AirwallexObject
{
}
