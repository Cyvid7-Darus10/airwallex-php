<?php

declare(strict_types=1);

namespace Airwallex\Tests;

use Airwallex\Exception\WebhookSignatureException;
use Airwallex\Webhooks;

final class WebhooksTest extends TestCase
{
    private const SECRET = 'whsec_test_secret';

    private static function sign(string $payload, string $timestamp, string $secret = self::SECRET): string
    {
        return hash_hmac('sha256', $timestamp . $payload, $secret);
    }

    /**
     * @param array<string, mixed> $event
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private static function delivery(array $event, ?string $timestamp = null): array
    {
        $payload = json_encode($event, JSON_THROW_ON_ERROR);
        $timestamp ??= (string) time();

        return [$payload, $timestamp, self::sign($payload, $timestamp)];
    }

    public function testValidSignatureConstructsTheEvent(): void
    {
        [$payload, $timestamp, $signature] = self::delivery([
            'id' => 'evt_1',
            'name' => 'transfer.settled',
            'account_id' => 'acct_1',
            'data' => ['object' => ['id' => 'tra_1', 'status' => 'PAID']],
        ]);

        $event = Webhooks::constructEvent($payload, $timestamp, $signature, self::SECRET);

        self::assertSame('evt_1', $event->id);
        self::assertSame('transfer.settled', $event->name);
        $data = $event->data;
        self::assertIsArray($data);
        $object = $data['object'] ?? null;
        self::assertIsArray($object);
        self::assertSame('tra_1', $object['id']);
    }

    public function testMillisecondTimestampsAreAccepted(): void
    {
        $timestamp = (string) (time() * 1000);
        $payload = '{"name":"transfer.settled"}';

        Webhooks::verifySignature($payload, $timestamp, self::sign($payload, $timestamp), self::SECRET);

        $this->addToAssertionCount(1);
    }

    public function testTamperedPayloadIsRejected(): void
    {
        [, $timestamp, $signature] = self::delivery(['name' => 'transfer.settled', 'data' => ['amount' => 100]]);
        $tampered = json_encode(['name' => 'transfer.settled', 'data' => ['amount' => 999999]], JSON_THROW_ON_ERROR);

        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessage('signature does not match');

        Webhooks::constructEvent($tampered, $timestamp, $signature, self::SECRET);
    }

    public function testWrongSecretIsRejected(): void
    {
        [$payload, $timestamp, $signature] = self::delivery(['name' => 'transfer.settled']);

        $this->expectException(WebhookSignatureException::class);

        Webhooks::constructEvent($payload, $timestamp, $signature, 'whsec_other_secret');
    }

    public function testStaleTimestampIsRejectedAsReplay(): void
    {
        [$payload, $timestamp, $signature] = self::delivery(
            ['name' => 'transfer.settled'],
            (string) (time() - 3600),
        );

        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessage('possible replay');

        Webhooks::constructEvent($payload, $timestamp, $signature, self::SECRET);
    }

    public function testFutureTimestampBeyondToleranceIsRejected(): void
    {
        [$payload, $timestamp, $signature] = self::delivery(
            ['name' => 'transfer.settled'],
            (string) (time() + 3600),
        );

        $this->expectException(WebhookSignatureException::class);

        Webhooks::verifySignature($payload, $timestamp, $signature, self::SECRET);
    }

    public function testNullToleranceSkipsTheReplayCheck(): void
    {
        [$payload, $timestamp, $signature] = self::delivery(
            ['name' => 'transfer.settled'],
            (string) (time() - 86400),
        );

        $event = Webhooks::constructEvent($payload, $timestamp, $signature, self::SECRET, toleranceSeconds: null);

        self::assertSame('transfer.settled', $event->name);
    }

    public function testNonNumericTimestampIsRejected(): void
    {
        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessage('Invalid x-timestamp');

        Webhooks::verifySignature('{}', 'yesterday', 'sig', self::SECRET);
    }

    public function testEmptySecretIsAProgrammerError(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Webhooks::verifySignature('{}', (string) time(), 'sig', '');
    }

    public function testValidSignatureOverNonJsonPayloadIsStillRejected(): void
    {
        $payload = 'not json at all';
        $timestamp = (string) time();

        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessage('not valid JSON');

        Webhooks::constructEvent($payload, $timestamp, self::sign($payload, $timestamp), self::SECRET);
    }

    public function testUnicodePayloadSignatureVerifies(): void
    {
        [$payload, $timestamp, $signature] = self::delivery([
            'name' => 'transfer.settled',
            'data' => ['reference' => 'Զեղում — 支付 🚀 «déjà vu»'],
        ]);

        $event = Webhooks::constructEvent($payload, $timestamp, $signature, self::SECRET);

        self::assertIsArray($event->data);
        self::assertSame('Զեղում — 支付 🚀 «déjà vu»', $event->data['reference']);
    }

    public function testSignatureComparisonIsAgainstHexDigest(): void
    {
        $payload = '{"name":"x"}';
        $timestamp = (string) time();

        // Correct digest but upper-cased: must NOT verify (byte-exact compare).
        $upper = strtoupper(self::sign($payload, $timestamp));

        $this->expectException(WebhookSignatureException::class);

        Webhooks::verifySignature($payload, $timestamp, $upper, self::SECRET);
    }
}
