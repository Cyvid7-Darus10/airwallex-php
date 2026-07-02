<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * An indicative FX rate from GET /api/v1/fx/rates/current (no funds move).
 *
 * @property-read string|null $currency_pair
 * @property-read float|int|null $rate indicative client rate (current API versions)
 * @property-read string|null $buy_currency
 * @property-read float|int|null $buy_amount
 * @property-read string|null $sell_currency
 * @property-read float|int|null $sell_amount
 * @property-read string|null $conversion_date
 * @property-read string|null $created_at
 * @property-read float|int|null $client_rate
 * @property-read float|int|null $mid_rate
 * @property-read string|null $dealt_currency
 * @property-read float|int|null $client_buy_amount
 * @property-read string|null $client_buy_currency
 * @property-read float|int|null $client_sell_amount
 * @property-read string|null $client_sell_currency
 * @property-read string|null $settlement_cutoff_time
 * @property-read string|null $settlement_date
 * @property-read list<array<string, mixed>>|null $rate_details
 */
final class RateQuote extends AirwallexObject
{
}
