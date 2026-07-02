<?php

declare(strict_types=1);

namespace Airwallex\Tests;

use Airwallex\AirwallexObject;
use Airwallex\Client;
use Airwallex\Resource\Account;
use Airwallex\Resource\BatchTransfer;
use Airwallex\Resource\Card;
use Airwallex\Resource\Cardholder;
use Airwallex\Resource\CardLimits;
use Airwallex\Resource\Conversion;
use Airwallex\Resource\ConversionAmendment;
use Airwallex\Resource\ConversionAmendmentQuote;
use Airwallex\Resource\Customer;
use Airwallex\Resource\CustomerClientSecret;
use Airwallex\Resource\FinancialTransaction;
use Airwallex\Resource\FxQuote;
use Airwallex\Resource\GlobalAccount;
use Airwallex\Resource\IssuingAuthorization;
use Airwallex\Resource\IssuingTransaction;
use Airwallex\Resource\PaymentIntent;
use Airwallex\Resource\RateQuote;
use Airwallex\Resource\Refund;
use Airwallex\Resource\Settlement;
use Airwallex\Resource\Transfer;
use Airwallex\Resource\WalletTransfer;
use Airwallex\Resource\WebhookEndpoint;
use Psr\Http\Message\RequestInterface;

/**
 * Wiring matrix: every service method must hit the documented endpoint with
 * the documented HTTP method and parameters.
 */
final class ServicesTest extends TestCase
{
    /**
     * Run one service call against a queue of [login, response] and return
     * the recorded data request.
     *
     * @param array<string, mixed>|list<mixed> $body
     */
    private function call(callable $call, array $body = ['id' => 'x']): RequestInterface
    {
        $client = $this->client([self::loginResponse(), self::json(200, $body)]);
        $this->lastResult = $call($client);

        return $this->dataRequests()[0];
    }

    private mixed $lastResult = null;

    /**
     * Assert the value returned by the last call() and hand it back typed.
     *
     * @template T of AirwallexObject
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function assertResult(string $class): AirwallexObject
    {
        self::assertInstanceOf($class, $this->lastResult);

        return $this->lastResult;
    }

    public function testAccounts(): void
    {
        $request = $this->call(fn (Client $c) => $c->accounts->retrieve());

        self::assertSame('GET', $request->getMethod());
        self::assertSame('/api/v1/account', $request->getUri()->getPath());
        $this->assertResult(Account::class);
    }

    public function testBalances(): void
    {
        $request = $this->call(fn (Client $c) => $c->balances->current(), []);
        self::assertSame('/api/v1/balances/current', $request->getUri()->getPath());

        $request = $this->call(
            fn (Client $c) => $c->balances->history(currency: 'USD', fromPostAt: '2026-01-01T00:00:00Z'),
            ['has_more' => false, 'items' => []],
        );
        self::assertSame('/api/v1/balances/history', $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $query);
        self::assertSame('USD', $query['currency']);
        self::assertSame('2026-01-01T00:00:00Z', $query['from_post_at']);
        self::assertSame('0', $query['page_num']);
    }

    public function testTransfers(): void
    {
        $request = $this->call(fn (Client $c) => $c->transfers->list(status: 'PAID'), ['has_more' => false, 'items' => []]);
        self::assertSame('GET', $request->getMethod());
        self::assertSame('/api/v1/transfers', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->transfers->retrieve('tra_1'));
        self::assertSame('/api/v1/transfers/tra_1', $request->getUri()->getPath());
        $this->assertResult(Transfer::class);

        $request = $this->call(fn (Client $c) => $c->transfers->create(['beneficiary_id' => 'ben_1']));
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/api/v1/transfers/create', $request->getUri()->getPath());
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));

        $request = $this->call(fn (Client $c) => $c->transfers->cancel('tra_1'));
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/api/v1/transfers/tra_1/cancel', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->transfers->validate(['beneficiary_id' => 'ben_1']));
        self::assertSame('/api/v1/transfers/validate', $request->getUri()->getPath());
        // Live API requires request_id in the validation payload too.
        self::assertArrayHasKey('request_id', self::bodyOf($request));

        $request = $this->call(fn (Client $c) => $c->transfers->confirmFunding('tra_1', ['funding_source_id' => 'fs_1']));
        self::assertSame('/api/v1/transfers/tra_1/confirm_funding', $request->getUri()->getPath());
        self::assertSame('fs_1', self::bodyOf($request)['funding_source_id']);

        // No params: confirm_funding must send an empty body, not "[]".
        $request = $this->call(fn (Client $c) => $c->transfers->confirmFunding('tra_1'));
        self::assertSame('', (string) $request->getBody());
    }

    public function testBatchTransfers(): void
    {
        $request = $this->call(fn (Client $c) => $c->batchTransfers->list(status: 'READY'), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/batch_transfers', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->batchTransfers->retrieve('bat_1'));
        self::assertSame('/api/v1/batch_transfers/bat_1', $request->getUri()->getPath());
        $this->assertResult(BatchTransfer::class);

        $request = $this->call(fn (Client $c) => $c->batchTransfers->create(['name' => 'run']));
        self::assertSame('/api/v1/batch_transfers/create', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->batchTransfers->addItems('bat_1', [['beneficiary_id' => 'ben_1']]));
        self::assertSame('/api/v1/batch_transfers/bat_1/add_items', $request->getUri()->getPath());
        self::assertSame([['beneficiary_id' => 'ben_1']], self::bodyOf($request)['items']);

        $request = $this->call(fn (Client $c) => $c->batchTransfers->deleteItems('bat_1', ['item_1']));
        self::assertSame('/api/v1/batch_transfers/bat_1/delete_items', $request->getUri()->getPath());
        self::assertSame(['item_1'], self::bodyOf($request)['item_ids']);

        $request = $this->call(fn (Client $c) => $c->batchTransfers->items('bat_1'), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/batch_transfers/bat_1/items', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->batchTransfers->quote('bat_1', ['validity' => 'HR_1']));
        self::assertSame('/api/v1/batch_transfers/bat_1/quote', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->batchTransfers->submit('bat_1'));
        self::assertSame('/api/v1/batch_transfers/bat_1/submit', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->batchTransfers->delete('bat_1'));
        self::assertSame('/api/v1/batch_transfers/bat_1/delete', $request->getUri()->getPath());
    }

    public function testWalletTransfers(): void
    {
        $request = $this->call(fn (Client $c) => $c->walletTransfers->list(), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/wallet_transfers', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->walletTransfers->retrieve('wt_1'));
        self::assertSame('/api/v1/wallet_transfers/wt_1', $request->getUri()->getPath());
        $this->assertResult(WalletTransfer::class);

        $request = $this->call(fn (Client $c) => $c->walletTransfers->create(['transfer_amount' => 1]));
        self::assertSame('/api/v1/wallet_transfers/create', $request->getUri()->getPath());
    }

    public function testBeneficiaries(): void
    {
        $request = $this->call(fn (Client $c) => $c->beneficiaries->list(bankAccountNumber: '123'), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/beneficiaries', $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $query);
        self::assertSame('123', $query['bank_account_number']);

        $request = $this->call(fn (Client $c) => $c->beneficiaries->retrieve('ben_1'));
        self::assertSame('/api/v1/beneficiaries/ben_1', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->beneficiaries->create(['nickname' => 'n']));
        self::assertSame('/api/v1/beneficiaries/create', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->beneficiaries->update('ben_1', ['nickname' => 'n']));
        self::assertSame('/api/v1/beneficiaries/update/ben_1', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->beneficiaries->delete('ben_1'));
        self::assertSame('/api/v1/beneficiaries/delete/ben_1', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->beneficiaries->validate(['beneficiary' => []]));
        self::assertSame('/api/v1/beneficiaries/validate', $request->getUri()->getPath());
    }

    public function testPayers(): void
    {
        $request = $this->call(fn (Client $c) => $c->payers->list(), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/payers', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->payers->retrieve('pay_1'));
        self::assertSame('/api/v1/payers/pay_1', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->payers->create(['nickname' => 'n']));
        self::assertSame('/api/v1/payers/create', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->payers->update('pay_1', ['nickname' => 'n']));
        self::assertSame('/api/v1/payers/update/pay_1', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->payers->delete('pay_1'));
        self::assertSame('/api/v1/payers/delete/pay_1', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->payers->validate(['payer' => []]));
        self::assertSame('/api/v1/payers/validate', $request->getUri()->getPath());
    }

    public function testConversionsAndRates(): void
    {
        $request = $this->call(fn (Client $c) => $c->conversions->list(buyCurrency: 'USD'), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/conversions', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->conversions->retrieve('con_1'));
        self::assertSame('/api/v1/conversions/con_1', $request->getUri()->getPath());
        $this->assertResult(Conversion::class);

        $request = $this->call(fn (Client $c) => $c->conversions->create(['buy_currency' => 'USD', 'term_agreement' => true]));
        self::assertSame('/api/v1/conversions/create', $request->getUri()->getPath());
        self::assertTrue(self::bodyOf($request)['term_agreement']);

        // Modern indicative-rates endpoint — NOT the legacy /rates/quote.
        $request = $this->call(
            fn (Client $c) => $c->rates->current(buyCurrency: 'USD', sellCurrency: 'SGD', buyAmount: 1000),
            ['currency_pair' => 'USDSGD', 'rate' => 1.34],
        );
        self::assertSame('GET', $request->getMethod());
        self::assertSame('/api/v1/fx/rates/current', $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $query);
        self::assertSame(['buy_currency' => 'USD', 'sell_currency' => 'SGD', 'buy_amount' => '1000'], $query);
        $this->assertResult(RateQuote::class);
        self::assertSame(1.34, $this->assertResult(RateQuote::class)->rate);
    }

    public function testFxQuotes(): void
    {
        $request = $this->call(fn (Client $c) => $c->fxQuotes->create(['buy_currency' => 'USD', 'validity' => 'HR_1']));
        self::assertSame('/api/v1/fx/quotes/create', $request->getUri()->getPath());
        $this->assertResult(FxQuote::class);

        $request = $this->call(fn (Client $c) => $c->fxQuotes->retrieve('quo_1'));
        self::assertSame('/api/v1/fx/quotes/quo_1', $request->getUri()->getPath());
    }

    public function testConversionAmendments(): void
    {
        $request = $this->call(fn (Client $c) => $c->conversionAmendments->list(conversionId: 'con_1'), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/conversion_amendments', $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $query);
        self::assertSame('con_1', $query['conversion_id']);

        $request = $this->call(fn (Client $c) => $c->conversionAmendments->retrieve('am_1'));
        self::assertSame('/api/v1/conversion_amendments/am_1', $request->getUri()->getPath());
        $this->assertResult(ConversionAmendment::class);

        $request = $this->call(fn (Client $c) => $c->conversionAmendments->create(['conversion_id' => 'con_1', 'type' => 'CANCELLATION']));
        self::assertSame('/api/v1/conversion_amendments/create', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->conversionAmendments->quote(['conversion_id' => 'con_1', 'type' => 'CANCELLATION']));
        self::assertSame('/api/v1/conversion_amendments/quote', $request->getUri()->getPath());
        $this->assertResult(ConversionAmendmentQuote::class);
        self::assertArrayHasKey('request_id', self::bodyOf($request));
    }

    public function testGlobalAccounts(): void
    {
        $request = $this->call(fn (Client $c) => $c->globalAccounts->list(countryCode: 'SG'), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/global_accounts', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->globalAccounts->retrieve('ga_1'));
        self::assertSame('/api/v1/global_accounts/ga_1', $request->getUri()->getPath());
        $this->assertResult(GlobalAccount::class);

        $request = $this->call(fn (Client $c) => $c->globalAccounts->create(['currency' => 'USD']));
        self::assertSame('/api/v1/global_accounts/create', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->globalAccounts->update('ga_1', ['nick_name' => 'ops']));
        self::assertSame('/api/v1/global_accounts/update/ga_1', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->globalAccounts->close('ga_1'));
        self::assertSame('/api/v1/global_accounts/ga_1/close', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->globalAccounts->transactions('ga_1'), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/global_accounts/ga_1/transactions', $request->getUri()->getPath());
    }

    public function testDeposits(): void
    {
        $request = $this->call(fn (Client $c) => $c->deposits->list(fromCreatedAt: '2026-01-01'), ['has_more' => false, 'items' => []]);
        self::assertSame('GET', $request->getMethod());
        self::assertSame('/api/v1/deposits', $request->getUri()->getPath());
    }

    public function testPaymentIntents(): void
    {
        $request = $this->call(fn (Client $c) => $c->paymentIntents->list(status: 'SUCCEEDED'), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/pa/payment_intents', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->paymentIntents->retrieve('int_1'));
        self::assertSame('/api/v1/pa/payment_intents/int_1', $request->getUri()->getPath());
        $this->assertResult(PaymentIntent::class);

        $request = $this->call(fn (Client $c) => $c->paymentIntents->create(['amount' => 25.0, 'currency' => 'USD']));
        self::assertSame('/api/v1/pa/payment_intents/create', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->paymentIntents->confirm('int_1', ['payment_method' => ['type' => 'card']]));
        self::assertSame('/api/v1/pa/payment_intents/int_1/confirm', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->paymentIntents->confirmContinue('int_1', ['type' => '3ds_continue']));
        self::assertSame('/api/v1/pa/payment_intents/int_1/confirm_continue', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->paymentIntents->capture('int_1', ['amount' => 25.0]));
        self::assertSame('/api/v1/pa/payment_intents/int_1/capture', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->paymentIntents->cancel('int_1'));
        self::assertSame('/api/v1/pa/payment_intents/int_1/cancel', $request->getUri()->getPath());
    }

    public function testCustomers(): void
    {
        $request = $this->call(fn (Client $c) => $c->customers->list(merchantCustomerId: 'm_1'), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/pa/customers', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->customers->retrieve('cus_1'));
        self::assertSame('/api/v1/pa/customers/cus_1', $request->getUri()->getPath());
        $this->assertResult(Customer::class);

        $request = $this->call(fn (Client $c) => $c->customers->create(['merchant_customer_id' => 'm_1']));
        self::assertSame('/api/v1/pa/customers/create', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->customers->update('cus_1', ['email' => 'a@b.c']));
        self::assertSame('/api/v1/pa/customers/cus_1/update', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->customers->generateClientSecret('cus_1'));
        self::assertSame('GET', $request->getMethod());
        self::assertSame('/api/v1/pa/customers/cus_1/generate_client_secret', $request->getUri()->getPath());
        $this->assertResult(CustomerClientSecret::class);
    }

    public function testRefunds(): void
    {
        $request = $this->call(fn (Client $c) => $c->refunds->list(paymentIntentId: 'int_1'), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/pa/refunds', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->refunds->retrieve('ref_1'));
        self::assertSame('/api/v1/pa/refunds/ref_1', $request->getUri()->getPath());
        $this->assertResult(Refund::class);

        $request = $this->call(fn (Client $c) => $c->refunds->create(['payment_intent_id' => 'int_1', 'amount' => 5.0]));
        self::assertSame('/api/v1/pa/refunds/create', $request->getUri()->getPath());
    }

    public function testIssuingCardholders(): void
    {
        $request = $this->call(fn (Client $c) => $c->issuingCardholders->list(email: 'a@b.c'), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/issuing/cardholders', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->issuingCardholders->retrieve('chd_1'));
        self::assertSame('/api/v1/issuing/cardholders/chd_1', $request->getUri()->getPath());
        $this->assertResult(Cardholder::class);

        $request = $this->call(fn (Client $c) => $c->issuingCardholders->create(['email' => 'a@b.c']));
        self::assertSame('/api/v1/issuing/cardholders/create', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->issuingCardholders->update('chd_1', ['mobile_number' => '+65']));
        self::assertSame('/api/v1/issuing/cardholders/chd_1/update', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->issuingCardholders->delete('chd_1'));
        self::assertSame('/api/v1/issuing/cardholders/chd_1/delete', $request->getUri()->getPath());
    }

    public function testIssuingCards(): void
    {
        $request = $this->call(fn (Client $c) => $c->issuingCards->list(cardStatus: 'ACTIVE'), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/issuing/cards', $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $query);
        self::assertSame('ACTIVE', $query['card_status']);

        $request = $this->call(fn (Client $c) => $c->issuingCards->retrieve('card_1'));
        self::assertSame('/api/v1/issuing/cards/card_1', $request->getUri()->getPath());
        $this->assertResult(Card::class);

        $request = $this->call(fn (Client $c) => $c->issuingCards->create(['cardholder_id' => 'chd_1']));
        self::assertSame('/api/v1/issuing/cards/create', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->issuingCards->update('card_1', ['nick_name' => 'travel']));
        self::assertSame('/api/v1/issuing/cards/card_1/update', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->issuingCards->activate('card_1'), []);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/api/v1/issuing/cards/card_1/activate', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->issuingCards->limits('card_1'));
        self::assertSame('GET', $request->getMethod());
        self::assertSame('/api/v1/issuing/cards/card_1/limits', $request->getUri()->getPath());
        $this->assertResult(CardLimits::class);
    }

    public function testIssuingTransactionsAndAuthorizations(): void
    {
        $request = $this->call(fn (Client $c) => $c->issuingTransactions->list(cardId: 'card_1'), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/issuing/transactions', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->issuingTransactions->retrieve('txn_1'));
        self::assertSame('/api/v1/issuing/transactions/txn_1', $request->getUri()->getPath());
        $this->assertResult(IssuingTransaction::class);

        $request = $this->call(fn (Client $c) => $c->issuingAuthorizations->list(status: 'PENDING'), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/issuing/authorizations', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->issuingAuthorizations->retrieve('auth_1'));
        self::assertSame('/api/v1/issuing/authorizations/auth_1', $request->getUri()->getPath());
        $this->assertResult(IssuingAuthorization::class);
    }

    public function testFinance(): void
    {
        $request = $this->call(fn (Client $c) => $c->financialTransactions->list(currency: 'USD'), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/pa/financial/transactions', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->financialTransactions->retrieve('ft_1'));
        self::assertSame('/api/v1/pa/financial/transactions/ft_1', $request->getUri()->getPath());
        $this->assertResult(FinancialTransaction::class);

        $request = $this->call(fn (Client $c) => $c->settlements->list(status: 'SETTLED'), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/pa/financial/settlements', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->settlements->retrieve('set_1'));
        self::assertSame('/api/v1/pa/financial/settlements/set_1', $request->getUri()->getPath());
        $this->assertResult(Settlement::class);
    }

    public function testReference(): void
    {
        $request = $this->call(fn (Client $c) => $c->reference->supportedCurrencies(), ['items' => []]);
        self::assertSame('/api/v1/reference/supported_currencies', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->reference->settlementAccounts(countryCode: 'SG', currency: 'SGD'), []);
        self::assertSame('/api/v1/reference/settlement_accounts', $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $query);
        self::assertSame(['country_code' => 'SG', 'currency' => 'SGD'], $query);

        $request = $this->call(fn (Client $c) => $c->reference->invalidConversionDates('USDSGD'), []);
        self::assertSame('/api/v1/reference/invalid_conversion_dates', $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $query);
        self::assertSame('USDSGD', $query['currency_pair']);
    }

    public function testWebhookEndpoints(): void
    {
        $request = $this->call(fn (Client $c) => $c->webhookEndpoints->list(), ['has_more' => false, 'items' => []]);
        self::assertSame('/api/v1/webhooks', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->webhookEndpoints->retrieve('hook_1'));
        self::assertSame('/api/v1/webhooks/hook_1', $request->getUri()->getPath());
        $this->assertResult(WebhookEndpoint::class);

        $request = $this->call(fn (Client $c) => $c->webhookEndpoints->create('https://example.com/hook', ['transfer.settled']));
        self::assertSame('/api/v1/webhooks/create', $request->getUri()->getPath());
        $body = self::bodyOf($request);
        self::assertSame('https://example.com/hook', $body['url']);
        self::assertSame(['transfer.settled'], $body['events']);
        self::assertArrayHasKey('request_id', $body);

        $request = $this->call(fn (Client $c) => $c->webhookEndpoints->update('hook_1', ['events' => ['transfer.failed']]));
        self::assertSame('/api/v1/webhooks/hook_1/update', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->webhookEndpoints->delete('hook_1'), []);
        self::assertSame('/api/v1/webhooks/hook_1/delete', $request->getUri()->getPath());
    }

    public function testSimulation(): void
    {
        $request = $this->call(fn (Client $c) => $c->simulation->createDeposit(['amount' => 1000, 'currency' => 'USD']), []);
        self::assertSame('/api/v1/simulation/deposit/create', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->simulation->settleDeposit('dep_1'), []);
        self::assertSame('/api/v1/simulation/deposits/dep_1/settle', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->simulation->rejectDeposit('dep_1'), []);
        self::assertSame('/api/v1/simulation/deposits/dep_1/reject', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->simulation->reverseDeposit('dep_1'), []);
        self::assertSame('/api/v1/simulation/deposits/dep_1/reverse', $request->getUri()->getPath());

        $request = $this->call(fn (Client $c) => $c->simulation->transitionTransfer('tra_1', ['next_status' => 'PAID']), []);
        self::assertSame('/api/v1/simulation/transfers/tra_1/transition', $request->getUri()->getPath());
        self::assertSame('PAID', self::bodyOf($request)['next_status']);

        $request = $this->call(fn (Client $c) => $c->simulation->transitionPayment('pay_1', ['next_status' => 'PAID']), []);
        self::assertSame('/api/v1/simulation/payments/pay_1/transition', $request->getUri()->getPath());
    }

    public function testNullFiltersAreOmittedFromQueries(): void
    {
        $request = $this->call(fn (Client $c) => $c->transfers->list(), ['has_more' => false, 'items' => []]);

        self::assertSame('page_num=0', $request->getUri()->getQuery());
    }
}
