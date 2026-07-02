<?php

declare(strict_types=1);

namespace Airwallex\Tests;

use Airwallex\AirwallexObject;
use Airwallex\Exception\AirwallexException;
use Airwallex\Resource\Transfer;

final class AirwallexObjectTest extends TestCase
{
    public function testUnknownFieldsFromNewerApiVersionsArePreserved(): void
    {
        $transfer = Transfer::make([
            'id' => 'tra_1',
            'status' => 'PAID',
            'brand_new_field_from_2027' => ['nested' => true],
        ]);

        self::assertSame('tra_1', $transfer->id);
        self::assertSame(['nested' => true], $transfer->brand_new_field_from_2027);
        self::assertArrayHasKey('brand_new_field_from_2027', $transfer->toArray());
    }

    public function testMissingFieldsReadAsNull(): void
    {
        $transfer = Transfer::make(['id' => 'tra_1']);

        self::assertNull($transfer->failure_reason);
        self::assertFalse(isset($transfer->failure_reason));
        self::assertTrue(isset($transfer->id));
    }

    public function testToArrayReturnsTheFullDecodedPayload(): void
    {
        $payload = ['id' => 'tra_1', 'beneficiary' => ['bank_details' => ['iban' => 'DE89']]];

        self::assertSame($payload, Transfer::make($payload)->toArray());
    }

    public function testObjectsAreImmutableViaPropertyWrite(): void
    {
        $transfer = Transfer::make(['id' => 'tra_1']);

        $this->expectException(\LogicException::class);

        /* @phpstan-ignore-next-line property.readOnly */
        $transfer->id = 'tra_2';
    }

    public function testObjectsAreImmutableViaArrayWrite(): void
    {
        $transfer = Transfer::make(['id' => 'tra_1']);

        $this->expectException(\LogicException::class);

        $transfer['id'] = 'tra_2';
    }

    public function testObjectsAreImmutableViaArrayUnset(): void
    {
        $transfer = Transfer::make(['id' => 'tra_1']);

        $this->expectException(\LogicException::class);

        unset($transfer['id']);
    }

    public function testArrayReadAccess(): void
    {
        $transfer = Transfer::make(['id' => 'tra_1']);

        self::assertSame('tra_1', $transfer['id']);
        self::assertTrue(isset($transfer['id']));
        self::assertFalse(isset($transfer['nope']));
        self::assertNull($transfer['nope']);
    }

    public function testJsonSerializeRoundTripsUnicode(): void
    {
        $payload = ['reference' => 'Invoice ₱5,000 — 発注書 🧾'];
        $object = AirwallexObject::make($payload);

        $encoded = json_encode($object, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        self::assertSame($payload, json_decode($encoded, true));
    }

    public function testToStringIsReadableAndRoundTrips(): void
    {
        $transfer = Transfer::make(['id' => 'tra_1', 'reference' => '支付 🚀']);

        $printed = (string) $transfer;

        self::assertStringStartsWith(Transfer::class . ' JSON: ', $printed);
        self::assertStringContainsString('"id": "tra_1"', $printed);
        self::assertStringContainsString('支付 🚀', $printed);
    }

    public function testMakeRejectsNonObjectPayloadsLoudly(): void
    {
        // A scalar where a resource was expected must surface immediately,
        // never turn into a silently-empty object.
        $this->expectException(AirwallexException::class);
        $this->expectExceptionMessage('Expected a JSON object');

        AirwallexObject::make('oops');
    }

    public function testMakeRejectsJsonNullPayloads(): void
    {
        $this->expectException(AirwallexException::class);

        Transfer::make(null);
    }

    public function testUnicodePayloadSurvivesTheFullRequestCycle(): void
    {
        $client = $this->client([
            self::loginResponse(),
            self::json(201, ['id' => 'tra_1', 'reference' => 'זהב 支付 🚀']),
        ]);

        $transfer = $client->transfers->create(['reference' => 'זהב 支付 🚀', 'beneficiary_id' => 'ben_1']);

        self::assertSame('זהב 支付 🚀', $transfer->reference);
        $sent = self::bodyOf($this->dataRequests()[0]);
        self::assertSame('זהב 支付 🚀', $sent['reference']);
    }
}
