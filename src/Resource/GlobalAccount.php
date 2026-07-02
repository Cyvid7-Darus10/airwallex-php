<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A local-currency account for receiving funds.
 *
 * @property-read string|null $id
 * @property-read string|null $request_id
 * @property-read string|null $account_name
 * @property-read string|null $account_number
 * @property-read string|null $account_routing_type
 * @property-read string|null $account_routing_value
 * @property-read string|null $branch_code
 * @property-read list<string>|null $clearing_systems
 * @property-read string|null $country_code
 * @property-read string|null $currency
 * @property-read string|null $institution_name
 * @property-read string|null $nick_name
 * @property-read list<string>|null $payment_methods
 * @property-read string|null $status
 * @property-read string|null $swift_code
 * @property-read string|null $registered_email
 * @property-read array<string, mixed>|null $alternate_account_identifiers
 */
final class GlobalAccount extends AirwallexObject
{
}
