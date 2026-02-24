<?php

declare(strict_types=1);

namespace Netresearch\NrXliffStreaming\Tests\Unit\Parser;

use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Integration test cases for XliffStreamingParser
 *
 * Tests real-world file operations and parser reusability
 *
 * @author Netresearch DTT GmbH
 */
#[CoversClass(XliffStreamingParser::class)]
final class XliffStreamingParserIntegrationTest extends UnitTestCase
{
    private XliffStreamingParser $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new XliffStreamingParser();
    }

    #[Test]
    public function canBeInstantiated(): void
    {
        $parser = new XliffStreamingParser();

        self::assertInstanceOf(XliffStreamingParser::class, $parser);
    }

    #[Test]
    public function parsesRealXliffFileFromFileSystem(): void
    {
        $fixturePath = __DIR__ . '/../Fixtures/valid/xliff-1.2-namespace.xlf';
        $xliffContent = file_get_contents($fixturePath);
        self::assertIsString($xliffContent, 'Failed to read fixture file');

        $units = iterator_to_array($this->subject->parseTransUnits($xliffContent));

        self::assertCount(1, $units);
        self::assertSame('test.key', $units[0]['id']);
        self::assertSame('Test', $units[0]['source']);
        self::assertSame('Testen', $units[0]['target']);
    }

    #[Test]
    public function parsesMultipleRealWorldXliffFiles(): void
    {
        $fixtureDir = __DIR__ . '/../Fixtures/valid/';
        $files = [
            'xliff-1.0-simple.xlf',
            'xliff-1.2-namespace.xlf',
            'xliff-2.0-segments.xlf',
        ];

        $totalUnits = 0;

        foreach ($files as $file) {
            $xliffContent = file_get_contents($fixtureDir . $file);
            self::assertIsString($xliffContent, 'Failed to read fixture file: ' . $file);
            $units = iterator_to_array($this->subject->parseTransUnits($xliffContent));
            $totalUnits += count($units);
        }

        self::assertSame(3, $totalUnits, 'Should parse 1 unit from each of 3 XLIFF files');
    }

    #[Test]
    public function canReuseParserInstanceForMultipleParses(): void
    {
        $xliff1 = file_get_contents(__DIR__ . '/../Fixtures/valid/xliff-1.0-simple.xlf');
        self::assertIsString($xliff1, 'Failed to read fixture file 1');
        $xliff2 = file_get_contents(__DIR__ . '/../Fixtures/valid/xliff-1.2-namespace.xlf');
        self::assertIsString($xliff2, 'Failed to read fixture file 2');

        // First parse
        $units1 = iterator_to_array($this->subject->parseTransUnits($xliff1));
        self::assertCount(1, $units1);

        // Second parse with same instance
        $units2 = iterator_to_array($this->subject->parseTransUnits($xliff2));
        self::assertCount(1, $units2);

        // Verify different results
        self::assertNotEquals($units1[0]['id'], $units2[0]['id']);
    }
}
