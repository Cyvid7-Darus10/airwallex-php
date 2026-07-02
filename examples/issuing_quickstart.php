<?php

declare(strict_types=1);

/**
 * Issuing walkthrough: cardholder -> virtual card -> limits & activity.
 *
 * Card numbers returned by this SDK are masked; the PCI-scoped endpoints
 * (/details, /provision_digital_token) are intentionally not implemented.
 *
 * Usage:
 *   export AIRWALLEX_CLIENT_ID=... AIRWALLEX_API_KEY=...
 *   php examples/issuing_quickstart.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Airwallex\Client;
use Airwallex\Env;

$client = new Client(env: Env::Demo);

// 1. A cardholder is the person the card is issued to.
$cardholder = $client->issuingCardholders->create([
    'email' => 'employee-' . bin2hex(random_bytes(4)) . '@example.com',
    'individual' => [
        'name' => ['first_name' => 'Ada', 'last_name' => 'Lovelace'],
        'date_of_birth' => '1990-01-01',
        // You must have collected the cardholder's consent to share their
        // data with Airwallex for card issuance.
        'express_consent_obtained' => 'yes',
        'address' => [
            'city' => 'Singapore',
            'country' => 'SG',
            'line1' => '1 Example Road',
            'postcode' => '018989',
        ],
    ],
    'type' => 'INDIVIDUAL',
]);
printf("Cardholder %s (%s)\n", (string) $cardholder->cardholder_id, (string) $cardholder->status);

// 2. Issue a virtual card. request_id is auto-generated, so a retried create
//    can never issue two cards.
try {
    $card = $client->issuingCards->create([
        'cardholder_id' => $cardholder->cardholder_id,
        'created_by' => 'Ada Lovelace',
        'form_factor' => 'VIRTUAL',
        'is_personalized' => false,
        'issue_to' => 'INDIVIDUAL',
        'program' => ['purpose' => 'COMMERCIAL'],
        'authorization_controls' => [
            'allowed_transaction_count' => 'MULTIPLE',
            'transaction_limits' => [
                'currency' => 'USD',
                // interval: PER_TRANSACTION | DAILY | WEEKLY | MONTHLY | ALL_TIME
                'limits' => [['amount' => 1000, 'interval' => 'MONTHLY']],
            ],
        ],
    ]);
} catch (Airwallex\Exception\ApiException $e) {
    // Accounts have an active-card cap; fall back to an existing card so the
    // rest of the walkthrough still demonstrates.
    printf("card create note: %s\n", $e->getMessage());
    $client->issuingCardholders->delete((string) $cardholder->cardholder_id);
    $card = $client->issuingCards->list(pageSize: 10)->items[0] ?? exit("no cards on this account\n");
}
printf("Card %s %s (%s)\n", (string) $card->card_id, (string) $card->card_number, (string) $card->card_status);

// 3. Remaining spend limits.
$limits = $client->issuingCards->limits((string) $card->card_id);
printf("Limits: %s\n", json_encode($limits->limits));

// 4. Activity: pending authorizations and cleared transactions.
foreach ($client->issuingAuthorizations->list(cardId: (string) $card->card_id, pageSize: 10) as $auth) {
    printf("  AUTH %s %s %s\n", (string) $auth->billing_amount, (string) $auth->billing_currency, (string) $auth->status);
}
foreach ($client->issuingTransactions->list(cardId: (string) $card->card_id, pageSize: 10) as $txn) {
    printf("  TXN  %s %s %s\n", (string) $txn->billing_amount, (string) $txn->billing_currency, (string) $txn->status);
}
