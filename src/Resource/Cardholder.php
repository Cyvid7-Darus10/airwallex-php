<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A person who can be issued corporate cards.
 *
 * @property-read string|null $cardholder_id
 * @property-read string|null $email
 * @property-read string|null $mobile_number
 * @property-read string|null $status
 * @property-read array<string, mixed>|null $individual
 * @property-read array<string, mixed>|null $address
 * @property-read array<string, mixed>|null $postal_address
 */
final class Cardholder extends AirwallexObject
{
}
