<?php

declare(strict_types=1);

/**
 * Collections walkthrough: global accounts (local receiving accounts) and
 * incoming deposits, with a simulated payer in the demo environment.
 *
 * Usage:
 *   export AIRWALLEX_CLIENT_ID=... AIRWALLEX_API_KEY=...
 *   php examples/global_accounts_and_deposits.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Airwallex\Client;
use Airwallex\Env;
use Airwallex\Exception\ApiException;

$client = new Client(env: Env::Demo);

// 1. Your local receiving accounts — share these details with payers.
$accounts = $client->globalAccounts->list(pageSize: 10);
foreach ($accounts as $account) {
    printf(
        "%s  %s %s  %s / %s (%s)\n",
        (string) $account->id,
        (string) $account->country_code,
        (string) $account->currency,
        (string) $account->account_number,
        (string) $account->account_routing_value,
        (string) $account->status,
    );
}

$first = $accounts->items[0] ?? null;
if ($first === null) {
    fwrite(STDERR, "No global accounts yet — open one first:\n");
    fwrite(STDERR, "  \$client->globalAccounts->create(['currency' => 'USD', 'country_code' => 'US', 'nick_name' => 'Ops USD']);\n");
    exit(0);
}

// 2. Demo only: simulate a payer sending money into the account.
$deposit = $client->simulation->createDeposit([
    'global_account_id' => $first->id,
    'amount' => 250,
    'currency' => $first->currency,
]);
$depositId = is_array($deposit) ? ($deposit['id'] ?? null) : null;
printf("simulated deposit %s\n", (string) $depositId);

if (is_string($depositId)) {
    // Simulated deposits take a moment to become settleable.
    sleep(3);
    try {
        $client->simulation->settleDeposit($depositId);
        echo "deposit settled\n";
    } catch (ApiException $e) {
        printf("settle note: %s (it may settle on its own shortly)\n", $e->getMessage());
    }
}

// 3. Money received into the account, newest first.
foreach ($client->globalAccounts->transactions((string) $first->id, pageSize: 10) as $txn) {
    printf("  %s  %s %s  %s\n", (string) $txn->transaction_date, (string) $txn->amount, (string) $txn->currency, (string) $txn->status);
}

// 4. All deposits across accounts.
foreach ($client->deposits->list(pageSize: 10) as $dep) {
    printf("deposit %s %s -> %s\n", (string) $dep->amount, (string) $dep->currency, (string) $dep->status);
}
