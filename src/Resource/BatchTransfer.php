<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A batch of payouts created, quoted and submitted as a unit.
 *
 * @property-read string|null $id
 * @property-read string|null $request_id
 * @property-read string|null $short_reference_id
 * @property-read string|null $status
 * @property-read string|null $name
 * @property-read string|null $remarks
 * @property-read array<string, mixed>|null $funding
 * @property-read array<string, mixed>|null $quote_summary
 * @property-read int|null $total_item_count
 * @property-read int|null $valid_item_count
 * @property-read string|null $transfer_date
 * @property-read array<string, mixed>|null $metadata
 * @property-read string|null $updated_at
 */
final class BatchTransfer extends AirwallexObject
{
}
