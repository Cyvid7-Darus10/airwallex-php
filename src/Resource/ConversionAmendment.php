<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * An amendment (e.g. cancellation) applied to an existing FX conversion.
 *
 * @property-read string|null $amendment_id
 * @property-read string|null $request_id
 * @property-read string|null $short_reference_id
 * @property-read string|null $conversion_id
 * @property-read string|null $type
 * @property-read string|null $status
 * @property-read list<array<string, mixed>>|null $charges
 * @property-read array<string, mixed>|null $metadata
 * @property-read string|null $created_at
 * @property-read string|null $updated_at
 */
final class ConversionAmendment extends AirwallexObject
{
}
