<?php

declare(strict_types=1);

namespace Airwallex\Resource;

use Airwallex\AirwallexObject;

/**
 * A payment collected from a shopper.
 *
 * @property-read string|null $id
 * @property-read string|null $request_id
 * @property-read string|null $status
 * @property-read float|int|null $amount
 * @property-read float|int|null $captured_amount
 * @property-read string|null $currency
 * @property-read string|null $merchant_order_id
 * @property-read string|null $invoice_id
 * @property-read string|null $payment_link_id
 * @property-read string|null $connected_account_id
 * @property-read string|null $conversion_quote_id
 * @property-read string|null $descriptor
 * @property-read string|null $return_url
 * @property-read string|null $client_secret
 * @property-read string|null $triggered_by
 * @property-read string|null $customer_id
 * @property-read array<string, mixed>|null $customer
 * @property-read string|null $payment_consent_id
 * @property-read array<string, mixed>|null $payment_consent
 * @property-read array<string, mixed>|null $payment_method_options
 * @property-read array<string, mixed>|null $latest_payment_attempt
 * @property-read array<string, mixed>|null $next_action
 * @property-read array<string, mixed>|null $order
 * @property-read array<string, mixed>|null $additional_info
 * @property-read list<array<string, mixed>>|null $funds_split_data
 * @property-read array<string, mixed>|null $risk_control_options
 * @property-read array<string, mixed>|null $metadata
 * @property-read string|null $cancellation_reason
 * @property-read string|null $cancelled_at
 * @property-read string|null $created_at
 * @property-read string|null $updated_at
 */
final class PaymentIntent extends AirwallexObject
{
}
