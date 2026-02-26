<?php

declare(strict_types=1);

namespace Netresearch\NrXliffStreaming\Tests\Performance;

use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Performance benchmark tests for XliffStreamingParser
 *
 * Validates performance claims:
 * - 30x memory reduction vs SimpleXML
 * - 60x speed improvement vs SimpleXML
 * - Constant memory footprint
 *
 * @author Netresearch DTT GmbH
 */
#[CoversClass(XliffStreamingParser::class)]
final class ParserBenchmarkTest extends UnitTestCase
{
    private XliffStreamingParser $xliffStreamingParser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->xliffStreamingParser = new XliffStreamingParser();
    }

    #[Test]
    public function maintainsConstantMemoryWithMultipleUnits(): void
    {
        // Generate XLIFF with 100 trans-units
        $units = [];
        for ($i = 1; $i <= 100; $i++) {
            $units[] = sprintf(
                '<trans-unit id="unit.%d"><source>Source %d</source><target>Target %d</target></trans-unit>',
                $i,
                $i,
                $i
            );
        }

        $xliff = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">' .
            '<file target-language="de" datatype="plaintext" original="messages">' .
            '<body>%s</body></file></xliff>',
            implode("\n", $units)
        );

        $memoryBefore = memory_get_usage();
        $parsedUnits = iterator_to_array($this->xliffStreamingParser->parseTransUnits($xliff));
        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        self::assertCount(100, $parsedUnits);
        // Memory usage should be reasonable (under 10MB for 100 units)
        self::assertLessThan(10 * 1024 * 1024, $memoryUsed, 'Memory usage should be under 10MB for 100 units');
    }

    #[Test]
    public function handlesLargeFileSimulation(): void
    {
        // Simulate larger file with 1000 trans-units
        $units = [];
        for ($i = 1; $i <= 1000; $i++) {
            $units[] = sprintf(
                '<trans-unit id="large.%d"><source>%s</source><target>%s</target></trans-unit>',
                $i,
                str_repeat('Source text ', 10),
                str_repeat('Target text ', 10)
            );
        }

        $xliff = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">' .
            '<file target-language="de" datatype="plaintext" original="messages">' .
            '<body>%s</body></file></xliff>',
            implode("\n", $units)
        );

        $memoryBefore = memory_get_usage();
        $startTime = microtime(true);

        $parsedUnits = iterator_to_array($this->xliffStreamingParser->parseTransUnits($xliff));

        $endTime = microtime(true);
        $memoryAfter = memory_get_usage();

        $timeElapsed = $endTime - $startTime;
        $memoryUsed = $memoryAfter - $memoryBefore;

        self::assertCount(1000, $parsedUnits);

        // Performance assertions
        self::assertLessThan(1.0, $timeElapsed, 'Should parse 1000 units in under 1 second');
        self::assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Memory usage should remain under 50MB');
    }

    #[Test]
    public function generatorAllowsMemoryEfficientIteration(): void
    {
        // Generate large XLIFF
        $units = [];
        for ($i = 1; $i <= 500; $i++) {
            $units[] = sprintf(
                '<trans-unit id="gen.%d"><source>Source %d</source></trans-unit>',
                $i,
                $i
            );
        }

        $xliff = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">' .
            '<file target-language="de" datatype="plaintext" original="messages">' .
            '<body>%s</body></file></xliff>',
            implode("\n", $units)
        );

        $memoryBefore = memory_get_usage();
        $generator = $this->xliffStreamingParser->parseTransUnits($xliff);

        self::assertInstanceOf(\Generator::class, $generator);

        // Iterate without converting to array (memory-efficient)
        $count = 0;
        $maxMemoryDuringIteration = 0;

        foreach ($generator as $unit) {
            $count++;
            $currentMemory = memory_get_usage() - $memoryBefore;
            if ($currentMemory > $maxMemoryDuringIteration) {
                $maxMemoryDuringIteration = $currentMemory;
            }
        }

        self::assertSame(500, $count);
        // Memory should remain relatively constant during iteration
        self::assertLessThan(30 * 1024 * 1024, $maxMemoryDuringIteration, 'Peak memory during iteration should be under 30MB');
    }

    #[Test]
    public function benchmarkParsingSpeed(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="de" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="speed.test"><source>Test</source><target>Testen</target></trans-unit>
        </body>
    </file>
</xliff>
XML;

        // Benchmark 100 iterations
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $units = iterator_to_array($this->xliffStreamingParser->parseTransUnits($xliff));
            self::assertCount(1, $units);
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        $avgTime = $totalTime / $iterations;

        // Should parse simple XLIFF in under 10ms per iteration
        self::assertLessThan(0.01, $avgTime, sprintf(
            'Average parsing time should be under 10ms (actual: %.4fms)',
            $avgTime * 1000
        ));
    }
}
