<?php

declare(strict_types=1);

namespace Netresearch\NrXliffStreaming\Tests\Unit\Parser;

use Netresearch\NrXliffStreaming\Exception\InvalidXliffException;
use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test cases for XliffStreamingParser
 *
 * @author Netresearch DTT GmbH
 */
#[CoversClass(XliffStreamingParser::class)]
final class XliffStreamingParserTest extends UnitTestCase
{
    private XliffStreamingParser $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new XliffStreamingParser();
    }

    #[Test]
    public function parsesValidXliff10WithoutNamespace(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<xliff version="1.0">
    <file source-language="en" target-language="de" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="component|type|placeholder">
                <source>Hello World</source>
                <target>Hallo Welt</target>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xliff));

        self::assertCount(1, $units);
        self::assertSame('component|type|placeholder', $units[0]['id']);
        self::assertSame('Hello World', $units[0]['source']);
        self::assertSame('Hallo Welt', $units[0]['target']);
    }

    #[Test]
    public function parsesValidXliff12WithNamespace(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="de" datatype="plaintext" original="messages" date="2025-01-12T10:00:00Z">
        <body>
            <trans-unit id="test.key">
                <source>Test</source>
                <target>Testen</target>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xliff));

        self::assertCount(1, $units);
        self::assertSame('test.key', $units[0]['id']);
        self::assertSame('Test', $units[0]['source']);
        self::assertSame('Testen', $units[0]['target']);
    }

    #[Test]
    public function parsesValidXliff20WithNamespace(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en" trgLang="de">
    <file id="f1" original="messages">
        <unit id="test.unit">
            <segment>
                <source>Test</source>
                <target>Testen</target>
            </segment>
        </unit>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xliff));

        // XLIFF 2.0 support with <unit> and <segment> structure
        self::assertCount(1, $units);
        self::assertSame('test.unit', $units[0]['id']);
        self::assertSame('Test', $units[0]['source']);
        self::assertSame('Testen', $units[0]['target']);
    }

    #[Test]
    public function parsesMultipleXliff20Units(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en" trgLang="de">
    <file id="f1" original="messages">
        <unit id="unit1">
            <segment>
                <source>First</source>
                <target>Erste</target>
            </segment>
        </unit>
        <unit id="unit2">
            <segment>
                <source>Second</source>
                <target>Zweite</target>
            </segment>
        </unit>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xliff));

        self::assertCount(2, $units);
        self::assertSame('unit1', $units[0]['id']);
        self::assertSame('First', $units[0]['source']);
        self::assertSame('Erste', $units[0]['target']);
        self::assertSame('unit2', $units[1]['id']);
        self::assertSame('Second', $units[1]['source']);
        self::assertSame('Zweite', $units[1]['target']);
    }

    #[Test]
    public function parsesMultipleTransUnits(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="de" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="key1">
                <source>One</source>
                <target>Eins</target>
            </trans-unit>
            <trans-unit id="key2">
                <source>Two</source>
                <target>Zwei</target>
            </trans-unit>
            <trans-unit id="key3">
                <source>Three</source>
                <target>Drei</target>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xliff));

        self::assertCount(3, $units);
        self::assertSame('key1', $units[0]['id']);
        self::assertSame('key2', $units[1]['id']);
        self::assertSame('key3', $units[2]['id']);
    }

    #[Test]
    public function fallsBackToSourceWhenTargetIsMissing(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="en" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="untranslated">
                <source>Untranslated text</source>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xliff));

        self::assertCount(1, $units);
        self::assertSame('untranslated', $units[0]['id']);
        self::assertSame('Untranslated text', $units[0]['source']);
        self::assertNull($units[0]['target'], 'Target should be null when not present');
    }

    #[Test]
    public function handlesEmptyXliffWithNoTransUnits(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="de" datatype="plaintext" original="messages">
        <body>
        </body>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xliff));

        self::assertCount(0, $units, 'Empty XLIFF should yield no trans-units');
    }

    #[Test]
    public function handlesMalformedXmlGracefully(): void
    {
        $invalidXml = '<xliff><file><body><trans-unit id="test"><source>Test</source></body></file></xliff>';

        // Malformed XML (mismatched tags) results in no trans-units being found
        // XMLReader emits warnings but doesn't find properly structured elements
        $units = iterator_to_array($this->subject->parseTransUnits($invalidXml));

        self::assertCount(0, $units, 'Malformed XML should result in no trans-units found');
    }

    #[Test]
    public function throwsExceptionForMissingIdAttribute(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="de" datatype="plaintext" original="messages">
        <body>
            <trans-unit>
                <source>Missing ID</source>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $this->expectException(InvalidXliffException::class);
        $this->expectExceptionCode(1700000004);
        $this->expectExceptionMessage('Missing required "id" attribute');

        iterator_to_array($this->subject->parseTransUnits($xliff));
    }

    #[Test]
    public function throwsExceptionForMissingSourceElement(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="de" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="missing-source">
                <target>Target without source</target>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $this->expectException(InvalidXliffException::class);
        $this->expectExceptionCode(1700000005);
        $this->expectExceptionMessage('Missing required <source> element');

        iterator_to_array($this->subject->parseTransUnits($xliff));
    }

    #[Test]
    public function handlesUtf8EncodingCorrectly(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="ja" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="unicode.test">
                <source>Hello 世界 🌍</source>
                <target>こんにちは 世界 🌏</target>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xliff));

        self::assertCount(1, $units);
        self::assertSame('Hello 世界 🌍', $units[0]['source']);
        self::assertSame('こんにちは 世界 🌏', $units[0]['target']);
    }

    #[Test]
    public function usesGeneratorForMemoryEfficiency(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="de" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="key1"><source>One</source></trans-unit>
            <trans-unit id="key2"><source>Two</source></trans-unit>
        </body>
    </file>
</xliff>
XML;

        $generator = $this->subject->parseTransUnits($xliff);

        self::assertInstanceOf(\Generator::class, $generator, 'Method must return a Generator');

        // Consume generator
        $count = 0;
        foreach ($generator as $unit) {
            $count++;
            self::assertArrayHasKey('id', $unit);
            self::assertArrayHasKey('source', $unit);
            self::assertArrayHasKey('target', $unit);
            self::assertArrayHasKey('line', $unit);
        }

        self::assertSame(2, $count);
    }
}
