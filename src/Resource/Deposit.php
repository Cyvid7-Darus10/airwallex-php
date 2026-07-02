<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A deposit received into the wallet.
 *
 * @property-read string|null $id
 * @property-read float|int|null $amount
 * @property-read string|null $currency
 * @property-read string|null $status
 * @property-read string|null $reference
 * @property-read array<string, mixed>|null $payer
 * @property-read array<string, mixed>|null $fee
 * @property-read string|null $funding_source_id
 * @property-read string|null $global_account_id
 * @property-read string|null $provider_transaction_id
 * @property-read string|null $estimated_settled_at
 * @property-read string|null $created_at
 */
final class Deposit extends AirwallexObject
{
}
