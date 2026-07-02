<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A transfer between Airwallex wallets.
 *
 * @property-read string|null $wallet_transfer_id
 * @property-read string|null $request_id
 * @property-read string|null $short_reference_id
 * @property-read string|null $status
 * @property-read float|int|null $transfer_amount
 * @property-read string|null $transfer_currency
 * @property-read array<string, mixed>|null $beneficiary
 * @property-read string|null $reason
 * @property-read string|null $reference
 * @property-read string|null $created_at
 * @property-read string|null $settled_at
 */
final class WalletTransfer extends AirwallexObject
{
}
