<?php

declare(strict_types=1);

/**
 * Payment acceptance walkthrough: intent -> (client-side confirm) -> refund.
 *
 * Usage:
 *   export AIRWALLEX_CLIENT_ID=... AIRWALLEX_API_KEY=...
 *   php examples/accept_payment.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Airwallex\Client;
use Airwallex\Env;

$client = new Client(env: Env::Demo);

// 1. Optionally attach the payment to a reusable customer.
$customer = $client->customers->create([
    'merchant_customer_id' => 'example-customer-' . bin2hex(random_bytes(4)),
    'email' => 'shopper@example.com',
]);
printf("Customer %s\n", (string) $customer->id);

// 2. Create the payment intent. request_id is auto-generated, so retrying a
//    timed-out create can never charge twice.
$intent = $client->paymentIntents->create([
    'amount' => 25.00,
    'currency' => 'USD',
    'merchant_order_id' => 'order-' . bin2hex(random_bytes(4)),
    'customer_id' => $customer->id,
]);
printf("Intent %s -> %s\n", (string) $intent->id, (string) $intent->status);

// 3. Hand $intent->client_secret to Airwallex's browser/mobile SDK to collect
//    card details PCI-safely; confirmation usually happens client-side.
//    Server-side confirm is also possible when you hold a payment consent:
//
//    $client->paymentIntents->confirm($intent->id, [
//        'payment_consent_reference' => ['id' => 'cst_...'],
//    ]);

// 4. Later: capture (for pre-auths), cancel, or refund.
//    Refunds are idempotent too — request_id is generated when omitted.
//
//    $refund = $client->refunds->create([
//        'payment_intent_id' => $intent->id,
//        'amount' => 5.00,
//        'reason' => 'Requested by customer',
//    ]);

// 5. Reconcile recent payments.
foreach ($client->paymentIntents->list(pageSize: 10) as $item) {
    printf("  %s  %s %s  %s\n", (string) $item->id, (string) $item->amount, (string) $item->currency, (string) $item->status);
}
