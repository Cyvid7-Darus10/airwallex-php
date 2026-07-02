<?php

declare(strict_types=1);

namespace Airwallex;

use Airwallex\Service\AbstractService;
use Airwallex\Service\AccountsService;
use Airwallex\Service\BalancesService;
use Airwallex\Service\BatchTransfersService;
use Airwallex\Service\BeneficiariesService;
use Airwallex\Service\ConversionAmendmentsService;
use Airwallex\Service\ConversionsService;
use Airwallex\Service\CustomersService;
use Airwallex\Service\DepositsService;
use Airwallex\Service\FinancialTransactionsService;
use Airwallex\Service\FxQuotesService;
use Airwallex\Service\GlobalAccountsService;
use Airwallex\Service\IssuingAuthorizationsService;
use Airwallex\Service\IssuingCardholdersService;
use Airwallex\Service\IssuingCardsService;
use Airwallex\Service\IssuingTransactionsService;
use Airwallex\Service\PayersService;
use Airwallex\Service\PaymentIntentsService;
use Airwallex\Service\RatesService;
use Airwallex\Service\ReferenceService;
use Airwallex\Service\RefundsService;
use Airwallex\Service\SettlementsService;
use Airwallex\Service\SimulationService;
use Airwallex\Service\TransfersService;
use Airwallex\Service\WalletTransfersService;
use Airwallex\Service\WebhookEndpointsService;
use Psr\Http\Client\ClientInterface;

/**
 * Airwallex API client.
 *
 *     $client = new \Airwallex\Client(env: \Airwallex\Env::Demo);
 *     // reads AIRWALLEX_CLIENT_ID / AIRWALLEX_API_KEY when not passed
 *
 *     foreach ($client->balances->current() as $balance) {
 *         echo $balance->currency, ' ', $balance->available_amount, PHP_EOL;
 *     }
 *
 * Authentication happens lazily on the first request; the bearer token is
 * cached on the client instance and refreshed automatically before it
 * expires, so one client can serve many calls in a long-lived process.
 *
 * @property-read AccountsService $accounts
 * @property-read BalancesService $balances
 * @property-read BatchTransfersService $batchTransfers
 * @property-read BeneficiariesService $beneficiaries
 * @property-read ConversionAmendmentsService $conversionAmendments
 * @property-read ConversionsService $conversions
 * @property-read CustomersService $customers
 * @property-read DepositsService $deposits
 * @property-read FinancialTransactionsService $financialTransactions
 * @property-read FxQuotesService $fxQuotes
 * @property-read GlobalAccountsService $globalAccounts
 * @property-read IssuingAuthorizationsService $issuingAuthorizations
 * @property-read IssuingCardholdersService $issuingCardholders
 * @property-read IssuingCardsService $issuingCards
 * @property-read IssuingTransactionsService $issuingTransactions
 * @property-read PayersService $payers
 * @property-read PaymentIntentsService $paymentIntents
 * @property-read RatesService $rates
 * @property-read ReferenceService $reference
 * @property-read RefundsService $refunds
 * @property-read SettlementsService $settlements
 * @property-read SimulationService $simulation
 * @property-read TransfersService $transfers
 * @property-read WalletTransfersService $walletTransfers
 * @property-read WebhookEndpointsService $webhookEndpoints
 */
class Client
{
    public const VERSION = '0.1.1';

    /**
     * @var array<string, class-string<AbstractService>>
     */
    private const SERVICES = [
        'accounts' => AccountsService::class,
        'balances' => BalancesService::class,
        'batchTransfers' => BatchTransfersService::class,
        'beneficiaries' => BeneficiariesService::class,
        'conversionAmendments' => ConversionAmendmentsService::class,
        'conversions' => ConversionsService::class,
        'customers' => CustomersService::class,
        'deposits' => DepositsService::class,
        'financialTransactions' => FinancialTransactionsService::class,
        'fxQuotes' => FxQuotesService::class,
        'globalAccounts' => GlobalAccountsService::class,
        'issuingAuthorizations' => IssuingAuthorizationsService::class,
        'issuingCardholders' => IssuingCardholdersService::class,
        'issuingCards' => IssuingCardsService::class,
        'issuingTransactions' => IssuingTransactionsService::class,
        'payers' => PayersService::class,
        'paymentIntents' => PaymentIntentsService::class,
        'rates' => RatesService::class,
        'reference' => ReferenceService::class,
        'refunds' => RefundsService::class,
        'settlements' => SettlementsService::class,
        'simulation' => SimulationService::class,
        'transfers' => TransfersService::class,
        'walletTransfers' => WalletTransfersService::class,
        'webhookEndpoints' => WebhookEndpointsService::class,
    ];

    private readonly ApiClient $api;

    /**
     * @var array<string, AbstractService>
     */
    private array $services = [];

    /**
     * @param string|null $clientId Airwallex client id (default: AIRWALLEX_CLIENT_ID env var).
     * @param string|null $apiKey Airwallex API key (default: AIRWALLEX_API_KEY env var).
     * @param Env $env Env::Production or Env::Demo (sandbox).
     * @param string|null $baseUrl Override the API host entirely (advanced; wins over $env). Must be https.
     * @param string|null $apiVersion Pin an x-api-version (e.g. "2024-08-07") instead of your account's default.
     * @param string|null $onBehalfOf Act on a connected account (sets x-on-behalf-of).
     * @param float $timeout Per-request timeout in seconds (applies to the default transport only).
     * @param int $maxRetries Automatic retries for transient failures (408/429/5xx/network).
     * @param ClientInterface|null $httpClient Bring your own PSR-18 client (proxies, custom TLS, ...);
     *                                         it is used as-is, never mutated or closed.
     */
    public function __construct(
        ?string $clientId = null,
        #[\SensitiveParameter]
        ?string $apiKey = null,
        Env $env = Env::Production,
        ?string $baseUrl = null,
        ?string $apiVersion = null,
        ?string $onBehalfOf = null,
        float $timeout = ClientConfig::DEFAULT_TIMEOUT_SECONDS,
        int $maxRetries = ClientConfig::DEFAULT_MAX_RETRIES,
        ?ClientInterface $httpClient = null,
    ) {
        $config = new ClientConfig(
            clientId: self::resolveCredential($clientId, 'AIRWALLEX_CLIENT_ID'),
            apiKey: self::resolveCredential($apiKey, 'AIRWALLEX_API_KEY'),
            env: $env,
            baseUrl: $baseUrl,
            apiVersion: $apiVersion,
            onBehalfOf: $onBehalfOf,
            timeout: $timeout,
            maxRetries: $maxRetries,
        );
        $this->api = new ApiClient($config, $httpClient);
    }

    /**
     * Lazily initialise services so constructing a client stays cheap.
     */
    public function __get(string $name): AbstractService
    {
        $class = self::SERVICES[$name] ?? null;
        if ($class === null) {
            throw new \InvalidArgumentException(\sprintf(
                'Unknown service "%s"; available services: %s',
                $name,
                implode(', ', array_keys(self::SERVICES)),
            ));
        }

        return $this->services[$name] ??= new $class($this->api);
    }

    public function __isset(string $name): bool
    {
        return isset(self::SERVICES[$name]);
    }

    /**
     * Call any Airwallex endpoint, including ones this SDK has no wrapper for.
     *
     * Authentication, retries, and error mapping still apply:
     *
     *     $disputes = $client->request('GET', '/api/v1/pa/payment_disputes', query: ['status' => 'OPEN']);
     *
     * @param array<string, mixed>|null $query
     * @param array<string, mixed>|null $body
     * @param array<string, string> $headers
     */
    public function request(
        string $method,
        string $path,
        ?array $query = null,
        ?array $body = null,
        array $headers = [],
    ): mixed {
        return $this->api->request($method, $path, $query, $body, $headers);
    }

    /**
     * The names of every service exposed on this client.
     *
     * @return list<string>
     */
    public static function serviceNames(): array
    {
        return array_keys(self::SERVICES);
    }

    private static function resolveCredential(?string $value, string $envVar): string
    {
        // $_ENV/$_SERVER first: phpdotenv v5 (Laravel/Symfony) populates those
        // without calling putenv(), so getenv() alone would miss .env files.
        $resolved = $value ?? $_ENV[$envVar] ?? $_SERVER[$envVar] ?? getenv($envVar);
        if (!\is_string($resolved) || $resolved === '') {
            throw new \InvalidArgumentException(\sprintf(
                'Missing credential: pass it to the client or set the %s environment variable.',
                $envVar,
            ));
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        // The nested config/token manager redact themselves, but keep the
        // top level minimal so a dumped client is never noisy or sensitive.
        return ['config' => $this->api->config()];
    }
}
