<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A payout to a beneficiary.
 *
 * Requires an API version of 2024-01-31 or later (earlier versions call
 * this resource "payments"). Set apiVersion on the client if your account
 * default is older.
 *
 * @property-read string|null $id
 * @property-read string|null $request_id
 * @property-read string|null $status
 * @property-read string|null $short_reference_id
 * @property-read float|int|null $source_amount
 * @property-read string|null $source_currency
 * @property-read float|int|null $transfer_amount
 * @property-read string|null $transfer_currency
 * @property-read string|null $transfer_method
 * @property-read string|null $transfer_date
 * @property-read float|int|null $amount_beneficiary_receives
 * @property-read float|int|null $amount_payer_pays
 * @property-read float|int|null $fee_amount
 * @property-read string|null $fee_currency
 * @property-read string|null $fee_paid_by
 * @property-read string|null $swift_charge_option
 * @property-read array<string, mixed>|null $beneficiary
 * @property-read string|null $beneficiary_id
 * @property-read array<string, mixed>|null $payer
 * @property-read string|null $reference
 * @property-read string|null $reason
 * @property-read string|null $remarks
 * @property-read array<string, mixed>|null $metadata
 * @property-read string|null $failure_reason
 * @property-read string|null $failure_type
 * @property-read string|null $created_at
 * @property-read string|null $updated_at
 */
final class Transfer extends AirwallexObject
{
}
