<?php

declare(strict_types=1);

namespace Airwallex\Tests;

use Airwallex\Exception\ApiException;
use Airwallex\Exception\BadRequestException;
use Airwallex\Exception\ConflictException;
use Airwallex\Exception\NotFoundException;
use Airwallex\Exception\PermissionDeniedException;
use Airwallex\Exception\RateLimitException;
use Airwallex\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;

final class ExceptionTest extends TestCase
{
    public function testErrorFieldsAreMappedIncludingRequestId(): void
    {
        $response = self::json(
            400,
            ['code' => 'validation_error', 'message' => 'transfer_amount is required', 'source' => 'transfer_amount'],
            ['x-request-id' => 'req_abc123'],
        );

        $exception = ApiException::fromResponse($response);

        self::assertInstanceOf(BadRequestException::class, $exception);
        self::assertSame(400, $exception->statusCode);
        self::assertSame('validation_error', $exception->errorCode);
        self::assertSame('transfer_amount', $exception->source);
        self::assertSame('req_abc123', $exception->requestId);
        self::assertIsArray($exception->body);
        self::assertSame(
            '[400] transfer_amount is required code=validation_error source=transfer_amount request_id=req_abc123',
            $exception->getMessage(),
        );
    }

    /**
     * @return iterable<string, array{int, class-string<ApiException>}>
     */
    public static function statusToClass(): iterable
    {
        yield '400' => [400, BadRequestException::class];
        yield '403' => [403, PermissionDeniedException::class];
        yield '404' => [404, NotFoundException::class];
        yield '409' => [409, ConflictException::class];
        yield '429' => [429, RateLimitException::class];
        yield '500' => [500, ServerException::class];
        yield '502' => [502, ServerException::class];
        yield '503' => [503, ServerException::class];
    }

    /**
     * @param class-string<ApiException> $class
     */
    #[DataProvider('statusToClass')]
    public function testStatusCodesMapToTypedExceptions(int $status, string $class): void
    {
        $api = $this->apiClient([
            self::loginResponse(),
            self::json($status, ['code' => 'x', 'message' => 'y']),
        ], maxRetries: 0);

        $this->expectException($class);

        $api->get('/api/v1/account');
    }

    public function testUnmappedStatusFallsBackToApiException(): void
    {
        $exception = ApiException::fromResponse(self::json(418, ['message' => 'teapot']));

        self::assertSame(ApiException::class, $exception::class);
        self::assertSame(418, $exception->statusCode);
    }

    public function testNonJsonErrorBodyUsesTextSnippet(): void
    {
        $response = new Response(502, [], '<html>Bad Gateway from nginx</html>');

        $exception = ApiException::fromResponse($response);

        self::assertInstanceOf(ServerException::class, $exception);
        self::assertNull($exception->errorCode);
        self::assertNull($exception->body);
        self::assertStringContainsString('Bad Gateway from nginx', $exception->getMessage());
    }

    public function testEmptyErrorBodyFallsBackToReasonPhrase(): void
    {
        $exception = ApiException::fromResponse(new Response(404, [], ''));

        self::assertInstanceOf(NotFoundException::class, $exception);
        self::assertStringContainsString('Not Found', $exception->getMessage());
    }

    public function testNonJson2xxBodyRaisesTypedException(): void
    {
        $api = $this->apiClient([
            self::loginResponse(),
            new Response(200, ['Content-Type' => 'text/html'], '<html>a captive proxy page</html>'),
        ]);

        try {
            $api->get('/api/v1/account');
            self::fail('expected ApiException');
        } catch (ApiException $exception) {
            self::assertSame(200, $exception->statusCode);
            self::assertStringContainsString('unparseable body', $exception->getMessage());
            self::assertStringContainsString('text/html', $exception->getMessage());
        }
    }

    public function testEmpty2xxBodyIsNull(): void
    {
        $api = $this->apiClient([self::loginResponse(), new Response(200, [], '')]);

        self::assertNull($api->post('/api/v1/issuing/cards/card_1/activate'));
    }

    public function testExceptionsNeverCarryTheAuthorizationHeader(): void
    {
        $api = $this->apiClient([
            self::loginResponse('tok_super_secret_token'),
            self::json(400, ['code' => 'validation_error', 'message' => 'bad'], ['x-request-id' => 'req_1']),
        ]);

        try {
            $api->get('/api/v1/account');
            self::fail('expected BadRequestException');
        } catch (ApiException $exception) {
            // Everything the SDK itself attaches to the exception must be
            // credential-free (backtrace args are a PHP runtime concern,
            // disabled in production via zend.exception_ignore_args).
            $flat = $exception->getMessage() . print_r([
                $exception->statusCode,
                $exception->errorCode,
                $exception->source,
                $exception->requestId,
                $exception->body,
            ], true);
            self::assertStringNotContainsString('tok_super_secret_token', $flat);
            self::assertStringNotContainsString(self::API_KEY, $flat);
        }
    }
}
