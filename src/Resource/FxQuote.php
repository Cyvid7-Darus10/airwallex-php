<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A lockable FX quote; pass its quote_id when executing a conversion to trade
 * at exactly the quoted rate.
 *
 * @property-read string|null $quote_id id under current API versions
 * @property-read string|null $id id under older API versions
 * @property-read string|null $usage e.g. MULTI_USE
 * @property-read string|null $valid_from_at
 * @property-read string|null $valid_to_at
 * @property-read string|null $request_id
 * @property-read string|null $status
 * @property-read string|null $currency_pair
 * @property-read float|int|null $buy_amount
 * @property-read string|null $buy_currency
 * @property-read float|int|null $sell_amount
 * @property-read string|null $sell_currency
 * @property-read string|null $dealt_currency
 * @property-read float|int|null $awx_rate
 * @property-read float|int|null $client_rate
 * @property-read float|int|null $mid_rate
 * @property-read list<array<string, mixed>>|null $rate_details
 * @property-read string|null $validity
 * @property-read string|null $conversion_date
 * @property-read string|null $expires_at
 * @property-read string|null $created_at
 */
final class FxQuote extends AirwallexObject
{
}
