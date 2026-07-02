<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * One payout draft inside a batch transfer.
 *
 * @property-read string|null $id
 * @property-read string|null $request_id
 * @property-read string|null $status
 * @property-read array<string, mixed>|null $transfer_draft
 * @property-read string|null $transfer_id
 * @property-read list<array<string, mixed>>|null $errors
 * @property-read string|null $updated_at
 */
final class BatchTransferItem extends AirwallexObject
{
}
