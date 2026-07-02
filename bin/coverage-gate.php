<?php

declare(strict_types=1);

/**
 * Fail the build when clover line coverage drops below the threshold.
 *
 * Usage: php bin/coverage-gate.php coverage.xml 85
 */

$file = $argv[1] ?? 'coverage.xml';
$threshold = (float) ($argv[2] ?? 85);

if (!is_file($file)) {
    fwrite(STDERR, "coverage-gate: clover file not found: {$file}\n");
    exit(1);
}

$xml = new SimpleXMLElement((string) file_get_contents($file));
$metrics = $xml->xpath('//project/metrics');
if ($metrics === false || $metrics === []) {
    fwrite(STDERR, "coverage-gate: no <metrics> element in {$file}\n");
    exit(1);
}

$statements = (int) $metrics[0]['statements'];
$covered = (int) $metrics[0]['coveredstatements'];

if ($statements === 0) {
    fwrite(STDERR, "coverage-gate: clover reports zero statements\n");
    exit(1);
}

$coverage = 100.0 * $covered / $statements;
printf("coverage-gate: %.2f%% lines covered (%d/%d), threshold %.2f%%\n", $coverage, $covered, $statements, $threshold);

exit($coverage + 1e-9 >= $threshold ? 0 : 1);
