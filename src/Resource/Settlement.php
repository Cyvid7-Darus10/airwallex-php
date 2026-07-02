<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * One settlement batch from GET /api/v1/pa/financial/settlements.
 *
 * @property-read string|null $id
 * @property-read string|null $currency
 * @property-read float|int|null $amount
 * @property-read float|int|null $fee
 * @property-read string|null $status
 * @property-read string|null $estimated_settled_at
 * @property-read string|null $settled_at
 * @property-read string|null $created_at
 */
final class Settlement extends AirwallexObject
{
}
