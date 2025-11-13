<?php

declare(strict_types=1);

namespace Netresearch\NrXliffStreaming\Tests\Functional\Parser;

use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test cases for XliffStreamingParser
 *
 * Tests TYPO3 integration and real-world file operations
 *
 * @author Netresearch DTT GmbH <info@netresearch.de>
 */
#[CoversClass(XliffStreamingParser::class)]
final class XliffStreamingParserFunctionalTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/nr_xliff_streaming'];

    private XliffStreamingParser $subject;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var XliffStreamingParser $parser */
        $parser = $this->get(XliffStreamingParser::class);
        $this->subject = $parser;
    }

    #[Test]
    public function canBeRetrievedFromDependencyInjectionContainer(): void
    {
        $parser = $this->get(XliffStreamingParser::class);

        self::assertInstanceOf(XliffStreamingParser::class, $parser);
    }

    #[Test]
    public function parsesRealXliffFileFromFileSystem(): void
    {
        $fixturePath = __DIR__ . '/../../Unit/Fixtures/valid/xliff-1.2-namespace.xlf';
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
        $fixtureDir = __DIR__ . '/../../Unit/Fixtures/valid/';
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
        $xliff1 = file_get_contents(__DIR__ . '/../../Unit/Fixtures/valid/xliff-1.0-simple.xlf');
        self::assertIsString($xliff1, 'Failed to read fixture file 1');
        $xliff2 = file_get_contents(__DIR__ . '/../../Unit/Fixtures/valid/xliff-1.2-namespace.xlf');
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
