<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A ledger entry behind a money movement on the account.
 *
 * @property-read string|null $id
 * @property-read string|null $batch_id
 * @property-read string|null $source_id
 * @property-read string|null $funding_source_id
 * @property-read string|null $source_type
 * @property-read string|null $transaction_type
 * @property-read string|null $currency
 * @property-read float|int|null $amount
 * @property-read float|int|null $net
 * @property-read float|int|null $fee
 * @property-read float|int|null $client_rate
 * @property-read string|null $currency_pair
 * @property-read string|null $description
 * @property-read string|null $status
 * @property-read string|null $estimated_settled_at
 * @property-read string|null $settled_at
 * @property-read string|null $created_at
 */
final class FinancialTransaction extends AirwallexObject
{
}
