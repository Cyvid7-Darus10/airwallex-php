<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * An issued corporate card.
 *
 * Card numbers returned here are masked; the PCI-scoped endpoints
 * (/details and /provision_digital_token) are intentionally not
 * implemented by this SDK.
 *
 * @property-read string|null $card_id
 * @property-read string|null $request_id
 * @property-read string|null $card_status
 * @property-read string|null $card_number
 * @property-read string|null $cardholder_id
 * @property-read string|null $brand
 * @property-read string|null $form_factor
 * @property-read string|null $type
 * @property-read string|null $issue_to
 * @property-read string|null $purpose
 * @property-read string|null $name_on_card
 * @property-read string|null $nick_name
 * @property-read string|null $note
 * @property-read string|null $client_data
 * @property-read string|null $created_by
 * @property-read bool|null $activate_on_issue
 * @property-read array<string, mixed>|null $authorization_controls
 * @property-read array<string, mixed>|null $postal_address
 * @property-read array<string, mixed>|null $primary_contact_details
 * @property-read array<string, mixed>|null $delivery_details
 * @property-read array<string, mixed>|null $metadata
 * @property-read int|null $card_version
 * @property-read list<array<string, mixed>>|null $all_card_versions
 * @property-read string|null $created_at
 */
final class Card extends AirwallexObject
{
}
