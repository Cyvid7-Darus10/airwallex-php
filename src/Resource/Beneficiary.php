<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A saved payout recipient.
 *
 * @property-read string|null $id id under current API versions
 * @property-read string|null $beneficiary_id id under older API versions
 * @property-read string|null $nickname
 * @property-read string|null $payer_entity_type
 * @property-read list<string>|null $payment_methods under older API versions
 * @property-read list<string>|null $transfer_methods under current API versions
 * @property-read array<string, mixed>|null $beneficiary
 */
final class Beneficiary extends AirwallexObject
{
}
