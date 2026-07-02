<?php

declare(strict_types=1);

/**
 * End-to-end payout walkthrough against the demo environment:
 * balances -> indicative rate -> beneficiary -> payout -> sandbox lifecycle.
 *
 * Usage:
 *   export AIRWALLEX_CLIENT_ID=... AIRWALLEX_API_KEY=...
 *   php examples/payout_quickstart.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Airwallex\Client;
use Airwallex\Env;
use Airwallex\Exception\ApiException;

$client = new Client(env: Env::Demo);

// 1. Wallet balances
foreach ($client->balances->current() as $balance) {
    if ((float) $balance->available_amount > 0) {
        printf("balance: %s %s\n", (string) $balance->currency, (string) $balance->available_amount);
    }
}

// 2. Indicative FX rate (no funds move)
$rate = $client->rates->current(buyCurrency: 'USD', sellCurrency: 'SGD', buyAmount: 1000);
printf("USD/SGD rate: %s\n", (string) $rate->rate);

// 3. Save a beneficiary (validate first — returns corridor-specific errors).
//    Field requirements vary by country/currency/transfer method; use
//    beneficiaries->validate() to check a payload without saving it.
$beneficiaryDetails = [
    'beneficiary' => [
        'entity_type' => 'PERSONAL',
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'bank_details' => [
            'account_currency' => 'USD',
            'account_name' => 'Ada Lovelace',
            'account_number' => '123456789',
            'bank_account_category' => 'Checking',
            'bank_country_code' => 'US',
            'account_routing_type1' => 'aba',
            'account_routing_value1' => '026009593',
        ],
        'address' => [
            'street_address' => '1 Example St',
            'city' => 'New York',
            'state' => 'NY',
            'postcode' => '10001',
            'country_code' => 'US',
        ],
    ],
    'nickname' => 'quickstart-example',
    'transfer_methods' => ['LOCAL'],
];

try {
    $client->beneficiaries->validate($beneficiaryDetails);
    $beneficiary = $client->beneficiaries->create($beneficiaryDetails);
    printf("beneficiary %s\n", (string) $beneficiary->id);

    // 4. Create the payout. request_id is auto-generated, so a retried or
    //    timed-out create can never pay twice.
    $transfer = $client->transfers->create([
        'beneficiary_id' => $beneficiary->id,
        'source_currency' => 'USD',
        'transfer_currency' => 'USD',
        'transfer_amount' => 10,
        'transfer_method' => 'LOCAL',
        'reference' => 'Invoice 42',
        'reason' => 'professional_service_fees',
    ]);
    printf("transfer %s -> %s\n", (string) $transfer->short_reference_id, (string) $transfer->status);

    // 5. Demo only: drive the transfer through its lifecycle instead of
    //    waiting for real settlement. Valid next_status values depend on the
    //    transfer's current state (the demo auto-advances SCHEDULED ->
    //    PROCESSING shortly after creation), so give it a moment first.
    sleep(3);
    try {
        $client->simulation->transitionTransfer((string) $transfer->id, ['next_status' => 'DISPATCHED']);
    } catch (ApiException $e) {
        printf("simulation note: %s\n", $e->getMessage());
    }
    printf("after simulation: %s\n", (string) $client->transfers->retrieve((string) $transfer->id)->status);

    // 6. Clean up the example beneficiary.
    $client->beneficiaries->delete((string) $beneficiary->id);
} catch (ApiException $exception) {
    // Typed errors carry the Airwallex code/source and the x-request-id to
    // quote to support. Nothing sensitive is ever attached.
    fwrite(STDERR, 'Airwallex error: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

// 7. Walk historical payouts across every page.
foreach ($client->transfers->list(pageSize: 50)->autoPagingIterator() as $item) {
    printf("%s %s %s\n", (string) $item->short_reference_id, (string) $item->status, (string) $item->reference);
}
