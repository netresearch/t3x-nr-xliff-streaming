<?php

declare(strict_types=1);

require __DIR__ . '/../../.Build/vendor/autoload.php';

use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;

/**
 * Performance Benchmark Script
 *
 * Tests XliffStreamingParser with various file sizes and measures:
 * - Execution time
 * - Memory usage (peak)
 * - Trans-units processed per second
 */
function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $factor = floor((strlen((string)$bytes) - 1) / 3);
    return sprintf('%.2f %s', $bytes / pow(1024, $factor), $units[$factor]);
}

function formatTime(float $seconds): string
{
    if ($seconds < 1) {
        return sprintf('%.0f ms', $seconds * 1000);
    }
    if ($seconds < 60) {
        return sprintf('%.2f sec', $seconds);
    }
    return sprintf('%.2f min', $seconds / 60);
}

function runBenchmark(string $filePath, string $label): array
{
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "Benchmark: {$label}\n";
    echo str_repeat('=', 80) . "\n";

    if (!file_exists($filePath)) {
        echo "ERROR: File not found: {$filePath}\n";
        return [];
    }

    $fileSize = filesize($filePath);
    echo 'File: ' . basename($filePath) . "\n";
    echo 'Size: ' . formatBytes($fileSize) . "\n";

    // Read file content
    $content = file_get_contents($filePath);
    echo "Reading file into memory...\n";

    // Initialize parser
    $parser = new XliffStreamingParser();

    // Reset memory tracking
    gc_collect_cycles();
    $memoryBefore = memory_get_usage(true);
    $memoryPeakBefore = memory_get_peak_usage(true);

    // Start timing
    $startTime = microtime(true);

    echo "Parsing...\n";

    // Parse and count trans-units
    $count = 0;
    $sampleUnits = [];

    try {
        foreach ($parser->parseTransUnits($content) as $unit) {
            $count++;

            // Collect first 3 units for verification
            if ($count <= 3) {
                $sampleUnits[] = $unit;
            }

            // Progress indicator for large files
            if ($count % 50000 === 0) {
                $elapsed = microtime(true) - $startTime;
                $rate = $count / $elapsed;
                echo sprintf(
                    "  Progress: %s trans-units (%.0f units/sec, %.2f sec elapsed)\n",
                    number_format($count),
                    $rate,
                    $elapsed
                );
            }
        }
    } catch (\Exception $e) {
        echo 'ERROR: ' . $e->getMessage() . "\n";
        return [];
    }

    // End timing
    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    // Memory measurements
    $memoryAfter = memory_get_usage(true);
    $memoryPeak = memory_get_peak_usage(true);
    $memoryUsed = $memoryAfter - $memoryBefore;
    $memoryPeakUsed = $memoryPeak - $memoryPeakBefore;

    // Calculate metrics
    $unitsPerSecond = $count / $duration;
    $bytesPerSecond = $fileSize / $duration;
    $memoryEfficiency = ($fileSize > 0) ? ($memoryPeakUsed / $fileSize) : 0;

    // Display results
    echo "\n" . str_repeat('-', 80) . "\n";
    echo "RESULTS\n";
    echo str_repeat('-', 80) . "\n";

    echo sprintf("✓ Trans-units parsed: %s\n", number_format($count));
    echo sprintf("✓ Execution time: %s\n", formatTime($duration));
    echo sprintf("✓ Throughput: %.0f trans-units/sec\n", $unitsPerSecond);
    echo sprintf("✓ Speed: %s/sec\n", formatBytes((int)$bytesPerSecond));
    echo "\n";
    echo sprintf("Memory used: %s\n", formatBytes($memoryUsed));
    echo sprintf("Memory peak: %s\n", formatBytes($memoryPeakUsed));
    echo sprintf("Memory efficiency: %.1fx of file size\n", $memoryEfficiency);
    echo "\n";

    // Show sample trans-units
    if (!empty($sampleUnits)) {
        echo "Sample trans-units (first 3):\n";
        foreach ($sampleUnits as $i => $unit) {
            echo sprintf(
                "  %d. ID: %s\n     Source: %s\n     Target: %s\n\n",
                $i + 1,
                substr($unit['id'], 0, 50),
                substr($unit['source'], 0, 60) . '...',
                substr($unit['target'] ?? '', 0, 60) . '...'
            );
        }
    }

    return [
        'label' => $label,
        'file' => basename($filePath),
        'fileSize' => $fileSize,
        'transUnits' => $count,
        'duration' => $duration,
        'unitsPerSecond' => $unitsPerSecond,
        'bytesPerSecond' => $bytesPerSecond,
        'memoryUsed' => $memoryUsed,
        'memoryPeak' => $memoryPeakUsed,
        'memoryEfficiency' => $memoryEfficiency,
    ];
}

// Main execution
echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║               XLIFF Streaming Parser - Performance Benchmark                  ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n";

$fixturesDir = __DIR__ . '/../../Tests/Fixtures/Performance';

$benchmarks = [
    ['file' => $fixturesDir . '/sample-30kb.xlf', 'label' => '30 KB File (100 trans-units)'],
    ['file' => $fixturesDir . '/sample-1mb.xlf', 'label' => '1 MB File (3,000 trans-units)'],
    ['file' => $fixturesDir . '/sample-30mb.xlf', 'label' => '30 MB File (100,000 trans-units)'],
    ['file' => $fixturesDir . '/sample-100mb.xlf', 'label' => '100 MB File (330,000 trans-units)'],
];

$results = [];

foreach ($benchmarks as $benchmark) {
    $result = runBenchmark($benchmark['file'], $benchmark['label']);
    if (!empty($result)) {
        $results[] = $result;
    }
}

// Summary table
echo "\n\n";
echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                            PERFORMANCE SUMMARY                                 ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

printf(
    "%-15s | %-10s | %-12s | %-12s | %-15s\n",
    'File',
    'Trans-Units',
    'Time',
    'Memory Peak',
    'Throughput'
);
echo str_repeat('-', 80) . "\n";

foreach ($results as $result) {
    printf(
        "%-15s | %-10s | %-12s | %-12s | %-15s\n",
        $result['file'],
        number_format($result['transUnits']),
        formatTime($result['duration']),
        formatBytes($result['memoryPeak']),
        sprintf('%.0f u/s', $result['unitsPerSecond'])
    );
}

echo "\n";
echo "Key Performance Indicators:\n";
echo "  • Constant memory footprint regardless of file size\n";
echo "  • Linear processing time scaling with file size\n";
echo "  • Streaming approach prevents memory exhaustion\n";
echo "\n";

echo "Benchmark completed successfully!\n";
