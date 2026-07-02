<?php

declare(strict_types=1);

/**
 * FX walkthrough: indicative rate -> lockable quote -> executed conversion.
 *
 * Usage:
 *   export AIRWALLEX_CLIENT_ID=... AIRWALLEX_API_KEY=...
 *   php examples/fx_quote_and_convert.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Airwallex\Client;
use Airwallex\Env;

$client = new Client(env: Env::Demo);

// 1. Indicative rate — informational only, nothing is booked.
$rate = $client->rates->current(buyCurrency: 'SGD', sellCurrency: 'USD', sellAmount: 1000);
printf("Indicative USD->SGD: %s (settles %s)\n", (string) $rate->rate, (string) $rate->conversion_date);

// 2. Lockable quote — holds this rate until it expires. Pass its quote_id to
//    a conversion to trade at exactly the quoted rate.
$quote = $client->fxQuotes->create([
    'sell_currency' => 'USD',
    'buy_currency' => 'SGD',
    'sell_amount' => 1000,
    'validity' => 'HR_1',       // MIN_1 | MIN_15 | MIN_30 | HR_1 | HR_4 | HR_8 | HR_24
]);
printf("Locked quote %s at %s until %s\n", (string) $quote->quote_id, (string) $quote->client_rate, (string) $quote->valid_to_at);

// 3. Execute a conversion. request_id is auto-generated so retries can never
//    double-convert; pass your own to control idempotency.
$conversion = $client->conversions->create([
    'sell_currency' => 'USD',
    'buy_currency' => 'SGD',
    'sell_amount' => 10,
    'term_agreement' => true,
    // 'quote_id' => $quote->quote_id,   // uncomment to trade at the locked rate
]);
printf(
    "Conversion %s: sold %s USD for %s SGD at %s (%s)\n",
    (string) $conversion->short_reference_id,
    (string) $conversion->sell_amount,
    (string) $conversion->buy_amount,
    (string) $conversion->client_rate,
    (string) $conversion->status,
);

// 4. Review conversion history.
foreach ($client->conversions->list(buyCurrency: 'SGD', pageSize: 10) as $past) {
    printf("  %s  %s -> %s  %s\n", (string) $past->conversion_date, (string) $past->sell_amount, (string) $past->buy_amount, (string) $past->status);
}
