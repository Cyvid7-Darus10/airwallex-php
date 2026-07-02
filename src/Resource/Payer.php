<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A saved payment sender used when creating transfers on behalf of a payer.
 *
 * @property-read string|null $payer_id
 * @property-read string|null $nickname
 * @property-read array<string, mixed>|null $payer
 */
final class Payer extends AirwallexObject
{
}
