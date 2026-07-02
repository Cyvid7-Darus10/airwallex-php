<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A transaction received into a global account.
 *
 * @property-read float|int|null $amount
 * @property-read string|null $currency
 * @property-read string|null $description
 * @property-read float|int|null $fee
 * @property-read string|null $payer_name
 * @property-read string|null $reference
 * @property-read string|null $status
 * @property-read string|null $transaction_date
 */
final class GlobalAccountTransaction extends AirwallexObject
{
}
