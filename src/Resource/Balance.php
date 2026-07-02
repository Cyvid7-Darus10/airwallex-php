<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * The current balance for one wallet currency.
 *
 * @property-read string|null $currency
 * @property-read float|int|null $available_amount
 * @property-read float|int|null $pending_amount
 * @property-read float|int|null $reserved_amount
 * @property-read float|int|null $total_amount
 */
final class Balance extends AirwallexObject
{
}
