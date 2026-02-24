<?php

declare(strict_types=1);

namespace Netresearch\NrXliffStreaming\Tests\Unit\Parser;

use Netresearch\NrXliffStreaming\Exception\InvalidXliffException;
use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Edge case test scenarios for XliffStreamingParser
 *
 * @author Netresearch DTT GmbH
 */
#[CoversClass(XliffStreamingParser::class)]
final class XliffStreamingParserEdgeCasesTest extends UnitTestCase
{
    private XliffStreamingParser $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new XliffStreamingParser();
    }

    #[Test]
    public function handlesEmptyStringIdAttribute(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="de" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="">
                <source>Empty ID</source>
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
    public function handlesVeryLongIdAttribute(): void
    {
        $longId = str_repeat('a', 1000);
        $xliff = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="de" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="{$longId}">
                <source>Long ID test</source>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xliff));

        self::assertCount(1, $units);
        self::assertSame($longId, $units[0]['id']);
        self::assertSame(1000, strlen($units[0]['id']));
    }

    #[Test]
    public function handlesSpecialCharactersInIdAttribute(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="de" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="component|type|placeholder[0]">
                <source>Special chars</source>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xliff));

        self::assertCount(1, $units);
        self::assertSame('component|type|placeholder[0]', $units[0]['id']);
    }

    #[Test]
    public function handlesWhitespaceInSourceElement(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="de" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="whitespace.test">
                <source>  Leading and trailing  </source>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xliff));

        self::assertCount(1, $units);
        self::assertSame('  Leading and trailing  ', $units[0]['source']);
    }

    #[Test]
    public function handlesXmlSpecialCharactersInContent(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="de" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="special.chars">
                <source>&lt;tag&gt; &amp; "quotes"</source>
                <target>&lt;Tag&gt; &amp; "Anführungszeichen"</target>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xliff));

        self::assertCount(1, $units);
        self::assertSame('<tag> & "quotes"', $units[0]['source']);
        self::assertSame('<Tag> & "Anführungszeichen"', $units[0]['target']);
    }

    #[Test]
    public function canParseMultipleTimesWithSameInstance(): void
    {
        $xliff1 = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="de" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="test1">
                <source>First</source>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $xliff2 = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="de" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="test2">
                <source>Second</source>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $units1 = iterator_to_array($this->subject->parseTransUnits($xliff1));
        $units2 = iterator_to_array($this->subject->parseTransUnits($xliff2));

        self::assertSame('test1', $units1[0]['id']);
        self::assertSame('test2', $units2[0]['id']);
    }

    #[Test]
    public function handlesEmptySourceElement(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="de" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="empty.source">
                <source></source>
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
    public function handlesEmptyTargetElement(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="de" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="empty.target">
                <source>Source text</source>
                <target></target>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xliff));

        self::assertCount(1, $units);
        self::assertSame('', $units[0]['target']);
    }

    #[Test]
    public function handlesXliff20MultipleSegments(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en" trgLang="de">
    <file id="f1" original="messages">
        <unit id="multi.segment">
            <segment>
                <source>First segment</source>
                <target>Erstes Segment</target>
            </segment>
        </unit>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xliff));

        self::assertCount(1, $units);
        self::assertSame('multi.segment', $units[0]['id']);
        self::assertSame('First segment', $units[0]['source']);
    }

    #[Test]
    public function handlesNestedGroupsInXliff20(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en" trgLang="de">
    <file id="f1" original="messages">
        <group id="g1">
            <unit id="grouped.unit">
                <segment>
                    <source>Grouped</source>
                    <target>Gruppiert</target>
                </segment>
            </unit>
        </group>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xliff));

        self::assertCount(1, $units);
        self::assertSame('grouped.unit', $units[0]['id']);
        self::assertSame('Grouped', $units[0]['source']);
    }
}
