<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * The quoted charges of a conversion amendment, without executing it.
 *
 * @property-read string|null $request_id
 * @property-read string|null $short_reference_id
 * @property-read string|null $conversion_id
 * @property-read string|null $type
 * @property-read list<array<string, mixed>>|null $charges
 * @property-read array<string, mixed>|null $metadata
 */
final class ConversionAmendmentQuote extends AirwallexObject
{
}
