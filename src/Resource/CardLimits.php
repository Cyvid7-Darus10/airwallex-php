<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A card's remaining spend and cash-withdrawal limits.
 *
 * @property-read string|null $currency
 * @property-read list<array<string, mixed>>|null $limits
 * @property-read list<array<string, mixed>>|null $cash_withdrawal_limits
 */
final class CardLimits extends AirwallexObject
{
}
