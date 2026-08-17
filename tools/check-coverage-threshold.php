<?php

declare(strict_types=1);

/**
 * Coverage threshold gate.
 *
 * Reads a Clover XML coverage report (produced by phpunit's
 * --coverage-clover flag) and exits non-zero if the line coverage
 * percentage is below THRESHOLD.
 *
 * Usage:
 *   php tools/check-coverage-threshold.php coverage.xml
 *
 * THRESHOLD is intentionally set just below the most recent baseline
 * to lock in a floor that future PRs can't drop below. Raise it as
 * more tests land.
 *
 * Lowered from 38.0 to 35.0 when the Todo module + generic period/node-tree
 * framework landed: a large slice of that code (the HTTP controller, the
 * DB-backed tree/generation services) is only exercisable by a consuming
 * application and lives in the Consumer-Only suite, which CI does not run.
 * The generic framework itself is unit-covered; raise the floor again as the
 * DB/HTTP paths gain fixture-backed tests.
 *
 * Raised from 35.0 to 44.5 once the data-driven validation matrix landed and
 * lifted real standalone Unit line coverage to ~45.09% (it instantiates every
 * rule-bearing model and drives the validator). The floor sits just below the
 * measured baseline so a regression trips it while normal fluctuation does
 * not. This gate measures the UNIT suite only. Combined
 * Unit+Feature+Integration coverage (~66%) is guarded separately by
 * tools/merge-coverage.php --min so Feature/Integration cannot silently
 * regress either.
 */
const THRESHOLD = 44.5;

if ($argc < 2) {
    fwrite(STDERR, "Usage: php {$argv[0]} <clover.xml>\n");
    exit(2);
}

$path = $argv[1];

if (! is_file($path)) {
    fwrite(STDERR, "Coverage file not found: {$path}\n");
    exit(2);
}

$xml = @simplexml_load_file($path);

if ($xml === false || ! isset($xml->project->metrics)) {
    fwrite(STDERR, "Invalid coverage XML: {$path}\n");
    exit(2);
}

$metrics = $xml->project->metrics;
$total = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$pct = $total > 0 ? round($covered / $total * 100, 2) : 0;

printf("Coverage: %d/%d lines (%.2f%%)\n", $covered, $total, $pct);
printf("Threshold: %.2f%%\n", THRESHOLD);

if ($pct < THRESHOLD) {
    fwrite(STDERR, sprintf(
        "FAIL: coverage %.2f%% is below threshold %.2f%%\n",
        $pct,
        THRESHOLD
    ));
    exit(1);
}

printf("PASS: coverage meets threshold.\n");
exit(0);
