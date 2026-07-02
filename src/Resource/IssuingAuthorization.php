<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A pending card authorization (read-only).
 *
 * @property-read string|null $transaction_id
 * @property-read string|null $status
 * @property-read string|null $card_id
 * @property-read string|null $card_nickname
 * @property-read string|null $masked_card_number
 * @property-read string|null $digital_wallet_token_id
 * @property-read float|int|null $transaction_amount
 * @property-read string|null $transaction_currency
 * @property-read float|int|null $billing_amount
 * @property-read string|null $billing_currency
 * @property-read list<array<string, mixed>>|null $fee_details
 * @property-read array<string, mixed>|null $merchant
 * @property-read string|null $acquiring_institution_identifier
 * @property-read string|null $auth_code
 * @property-read string|null $network_transaction_id
 * @property-read string|null $retrieval_ref
 * @property-read string|null $lifecycle_id
 * @property-read string|null $updated_by_transaction
 * @property-read array<string, mixed>|null $risk_details
 * @property-read string|null $failure_reason
 * @property-read string|null $client_data
 * @property-read string|null $create_time
 * @property-read string|null $expiry_date
 */
final class IssuingAuthorization extends AirwallexObject
{
}
