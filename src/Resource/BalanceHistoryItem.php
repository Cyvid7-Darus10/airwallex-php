<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * One ledger movement from GET /api/v1/balances/history.
 *
 * @property-read string|null $currency
 * @property-read float|int|null $amount
 * @property-read float|int|null $balance
 * @property-read float|int|null $fee
 * @property-read string|null $description
 * @property-read string|null $source
 * @property-read string|null $source_type
 * @property-read string|null $posted_at
 */
final class BalanceHistoryItem extends AirwallexObject
{
}
