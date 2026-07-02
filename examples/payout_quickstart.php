<?php

declare(strict_types=1);

/**
 * End-to-end payout walkthrough against the demo environment.
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
    printf("balance: %s %s\n", $balance->currency, (string) $balance->available_amount);
}

// 2. Indicative FX rate (no funds move)
$rate = $client->rates->current(buyCurrency: 'USD', sellCurrency: 'SGD', buyAmount: 1000);
printf("USD/SGD client rate: %s\n", (string) $rate->client_rate);

// 3. Create and settle a payout
try {
    $transfer = $client->transfers->create([
        'beneficiary_id' => 'ben_replace_me',
        'source_currency' => 'USD',
        'transfer_currency' => 'PHP',
        'transfer_amount' => 100,
        'transfer_method' => 'LOCAL',
        'reference' => 'Invoice 42',
        'reason' => 'professional_service_fees',
    ]);
    printf("transfer %s -> %s\n", (string) $transfer->id, (string) $transfer->status);

    // Demo only: push the transfer through its lifecycle.
    $client->simulation->transitionTransfer((string) $transfer->id, ['next_status' => 'PAID']);
} catch (ApiException $exception) {
    fwrite(STDERR, 'Airwallex error: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

// 4. Walk every historical transfer, newest pages first
foreach ($client->transfers->list(pageSize: 50)->autoPagingIterator() as $item) {
    printf("%s %s %s\n", (string) $item->id, (string) $item->status, (string) $item->reference);
}
