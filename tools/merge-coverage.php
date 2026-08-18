<?php

declare(strict_types=1);

/**
 * Merge multiple Clover XML coverage reports into one combined line-coverage
 * number.
 *
 * The Unit, Feature, and Integration suites each exercise a different slice of
 * ./src. A line counts as "covered" if ANY suite hit it, so the combined
 * figure is the union of covered lines across every input report — the honest
 * measure of how much of the package the whole test corpus touches.
 *
 * Usage:
 *   php tools/merge-coverage.php <clover1.xml> [<clover2.xml> ...]
 *   php tools/merge-coverage.php --json out.json <clover1.xml> ...
 *   php tools/merge-coverage.php --min 63.0 <clover1.xml> ...
 *
 * Output: prints "covered/total (pct%)" and, per file, the union counts.
 * Exit code 0 on success, 1 if --min is given and combined coverage is below
 * it (the CI floor), 2 on bad input.
 *
 * Clover line elements we care about are `<line type="stmt" num=".." count=".."/>`.
 * We key each executable statement by "file:num" and OR its covered flag across
 * all reports.
 */
$args = $argv;
array_shift($args); // drop script name

$jsonOut = null;
$min = null;

// Parse leading options in any order.
while (isset($args[0]) && str_starts_with((string) $args[0], '--')) {
    $opt = array_shift($args);
    if ($opt === '--json') {
        $jsonOut = array_shift($args);
    } elseif ($opt === '--min') {
        $min = (float) array_shift($args);
    } else {
        fwrite(STDERR, "Unknown option: {$opt}\n");
        exit(2);
    }
}

if (count($args) === 0) {
    fwrite(STDERR, "Usage: php tools/merge-coverage.php [--json out.json] [--min PCT] <clover.xml> [<clover.xml> ...]\n");
    exit(2);
}

/**
 * @var array<string, bool> $lines  key "file\0num" => covered
 */
$lines = [];

foreach ($args as $path) {
    if (! is_file($path)) {
        fwrite(STDERR, "Coverage file not found: {$path}\n");
        exit(2);
    }

    $xml = @simplexml_load_file($path);
    if ($xml === false || ! isset($xml->project)) {
        fwrite(STDERR, "Invalid coverage XML: {$path}\n");
        exit(2);
    }

    foreach ($xml->xpath('//file') as $file) {
        $name = (string) $file['name'];
        foreach ($file->line as $line) {
            if ((string) $line['type'] !== 'stmt') {
                continue;
            }
            $num = (string) $line['num'];
            $key = $name."\0".$num;
            $covered = ((int) $line['count']) > 0;

            // Union: once covered by any suite, stays covered.
            $lines[$key] = ($lines[$key] ?? false) || $covered;
        }
    }
}

$total = count($lines);
$covered = 0;
$perFile = [];

foreach ($lines as $key => $isCovered) {
    [$name] = explode("\0", $key, 2);
    $perFile[$name] ??= ['total' => 0, 'covered' => 0];
    $perFile[$name]['total']++;
    if ($isCovered) {
        $covered++;
        $perFile[$name]['covered']++;
    }
}

$pct = $total > 0 ? round($covered / $total * 100, 2) : 0.0;

printf("Combined coverage (union of %d report(s)): %d/%d lines (%.2f%%)\n", count($args), $covered, $total, $pct);

if ($jsonOut !== null) {
    ksort($perFile);
    file_put_contents($jsonOut, json_encode([
        'covered' => $covered,
        'total' => $total,
        'percent' => $pct,
        'reports' => $args,
        'files' => $perFile,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
}

if ($min !== null) {
    printf("Combined floor: %.2f%%\n", $min);
    if ($pct < $min) {
        fwrite(STDERR, sprintf(
            "FAIL: combined coverage %.2f%% is below floor %.2f%%\n",
            $pct,
            $min
        ));
        exit(1);
    }
    printf("PASS: combined coverage meets floor.\n");
}

exit(0);
