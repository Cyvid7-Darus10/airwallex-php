<?php

declare(strict_types=1);

namespace Airwallex\Tests;

use Airwallex\Client;

/**
 * Guards against the classic stale-release mistakes: tagging a version whose
 * constant or changelog was never updated.
 */
final class ReleaseConsistencyTest extends TestCase
{
    public function testVersionConstantIsSemver(): void
    {
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', Client::VERSION);
    }

    public function testChangelogDocumentsTheCurrentVersion(): void
    {
        $changelog = (string) file_get_contents(__DIR__ . '/../CHANGELOG.md');

        self::assertStringContainsString('## [' . Client::VERSION . ']', $changelog);
    }
}
