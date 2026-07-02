<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A shopper whose payment details can be saved.
 *
 * @property-read string|null $id
 * @property-read string|null $request_id
 * @property-read string|null $merchant_customer_id
 * @property-read string|null $first_name
 * @property-read string|null $last_name
 * @property-read string|null $business_name
 * @property-read string|null $email
 * @property-read string|null $phone_number
 * @property-read array<string, mixed>|null $address
 * @property-read string|null $client_secret
 * @property-read array<string, mixed>|null $metadata
 * @property-read string|null $created_at
 * @property-read string|null $updated_at
 */
final class Customer extends AirwallexObject
{
}
