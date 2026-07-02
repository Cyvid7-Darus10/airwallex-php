<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * An FX conversion between wallet currencies.
 *
 * @property-read string|null $conversion_id
 * @property-read string|null $request_id
 * @property-read string|null $short_reference_id
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
 * @property-read string|null $quote_id
 * @property-read string|null $conversion_date
 * @property-read string|null $settlement_cutoff_time
 * @property-read string|null $reason
 * @property-read string|null $created_at
 * @property-read string|null $last_updated_at
 */
final class Conversion extends AirwallexObject
{
}
