<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A full or partial refund of a payment.
 *
 * @property-read string|null $id
 * @property-read string|null $request_id
 * @property-read string|null $status
 * @property-read float|int|null $amount
 * @property-read string|null $currency
 * @property-read string|null $reason
 * @property-read string|null $payment_intent_id
 * @property-read string|null $payment_attempt_id
 * @property-read string|null $acquirer_reference_number
 * @property-read array<string, mixed>|null $failure_details
 * @property-read array<string, mixed>|null $metadata
 * @property-read string|null $created_at
 * @property-read string|null $updated_at
 */
final class Refund extends AirwallexObject
{
}
