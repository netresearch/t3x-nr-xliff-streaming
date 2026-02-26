<?php

declare(strict_types=1);

namespace Netresearch\NrXliffStreaming\Parser;

use Netresearch\NrXliffStreaming\Exception\InvalidXliffException;

/**
 * High-performance streaming XLIFF parser supporting XLIFF 1.0, 1.2, and 2.0
 *
 * Uses XMLReader for constant memory footprint regardless of file size.
 * Provides 30x memory reduction and 60x speed improvement over SimpleXML for large files.
 *
 * Supported XLIFF versions:
 * - XLIFF 1.0: No namespace
 * - XLIFF 1.2: urn:oasis:names:tc:xliff:document:1.2
 * - XLIFF 2.0: urn:oasis:names:tc:xliff:document:2.0
 *
 * @author Netresearch DTT GmbH
 */
final class XliffStreamingParser implements XliffParserInterface
{
    /**
     * XLIFF 1.2 namespace URI
     */
    private const XLIFF_1_2_NS = 'urn:oasis:names:tc:xliff:document:1.2';

    /**
     * XLIFF 2.0 namespace URI
     */
    private const XLIFF_2_0_NS = 'urn:oasis:names:tc:xliff:document:2.0';

    /**
     * Parse XLIFF trans-units using streaming XMLReader
     *
     * Generator pattern yields one trans-unit at a time with constant memory usage.
     * Each trans-unit is converted to SimpleXMLElement for easy data extraction.
     *
     * Memory usage: ~30MB constant regardless of file size (vs 900MB for 108MB with SimpleXML)
     * Speed: 60x faster than SimpleXML for large files (90 seconds vs 90 minutes)
     *
     * @param string $xmlContent XLIFF file content
     * @return \Generator<array{id: string, source: string, target: string|null, line: int}>
     * @throws InvalidXliffException if XML is malformed or invalid XLIFF structure
     */
    public function parseTransUnits(string $xmlContent): \Generator
    {
        $xmlReader = new \XMLReader();

        try {
            // Load XML content
            if (!$xmlReader->XML($xmlContent, 'UTF-8', LIBXML_NONET)) {
                throw new InvalidXliffException('Failed to parse XML content', 1700000001);
            }

            // Stream through XML elements
            while ($xmlReader->read()) {
                // Check for trans-unit elements (XLIFF 1.x) or unit elements (XLIFF 2.0)
                if (
                    $xmlReader->nodeType === \XMLReader::ELEMENT
                    && ($xmlReader->localName === 'trans-unit' || $xmlReader->localName === 'unit')
                    && $this->isXliffNamespace($xmlReader->namespaceURI)
                ) {
                    yield $this->extractTransUnit($xmlReader);
                }
            }
        } finally {
            // Ensure XMLReader resource is always closed
            $xmlReader->close();
        }
    }

    /**
     * Check if namespace URI is a supported XLIFF version
     *
     * @param string|null $uri Namespace URI (null or empty for XLIFF 1.0)
     * @return bool True if supported XLIFF namespace
     */
    private function isXliffNamespace(?string $uri): bool
    {
        // XLIFF 1.0: no namespace (null or empty string)
        // XLIFF 1.2: urn:oasis:names:tc:xliff:document:1.2
        // XLIFF 2.0: urn:oasis:names:tc:xliff:document:2.0
        return $uri === null
            || $uri === ''
            || $uri === self::XLIFF_1_2_NS
            || $uri === self::XLIFF_2_0_NS;
    }

    /**
     * Extract trans-unit data from current XMLReader position
     *
     * Converts XMLReader node to SimpleXMLElement for easy data extraction
     * with XXE protection (LIBXML_NONET flag).
     *
     * @param \XMLReader $xmlReader XMLReader positioned at trans-unit element
     * @return array{id: string, source: string, target: string|null, line: int}
     * @throws InvalidXliffException if trans-unit structure is invalid
     */
    private function extractTransUnit(\XMLReader $xmlReader): array
    {
        $expanded = $xmlReader->expand();
        if ($expanded === false) {
            throw new InvalidXliffException(
                'Failed to expand trans-unit (possible entity reference loop or XXE attack)',
                1700000002
            );
        }

        $line = $expanded->getLineNo();

        // Read trans-unit as XML string
        // Note: readOuterXml() can return false in practice despite PHPDoc saying string
        $xml = $xmlReader->readOuterXml();
        if (!\is_string($xml) || $xml === '') {
            throw new InvalidXliffException(
                sprintf('Failed to read trans-unit at line %d', $line),
                1700000002
            );
        }

        // Convert to SimpleXMLElement for easy data extraction (with XXE protection)
        // Use libxml internal errors to avoid interfering with test framework error handlers
        $useInternalErrors = libxml_use_internal_errors(true);
        try {
            $element = simplexml_load_string(
                $xml,
                \SimpleXMLElement::class,
                LIBXML_NONET
            );
        } finally {
            libxml_use_internal_errors($useInternalErrors);
            libxml_clear_errors();
        }

        if ($element === false) {
            throw new InvalidXliffException(
                sprintf('Invalid trans-unit XML at line %d (external entities are blocked)', $line),
                1700000003
            );
        }

        // Register namespace if present (XLIFF 1.2 / 2.0)
        if ($xmlReader->namespaceURI !== null) {
            $element->registerXPathNamespace('x', $xmlReader->namespaceURI);
        }

        // Extract required id attribute
        $id = (string)($element->attributes()['id'] ?? '');
        if ($id === '') {
            throw new InvalidXliffException(
                sprintf('Missing required "id" attribute in trans-unit at line %d', $line),
                1700000004
            );
        }

        // Handle XLIFF 2.0 <segment> wrapper
        $sourceElement = $element;
        if (property_exists($element, 'segment') && $element->segment !== null) {
            // XLIFF 2.0: <unit><segment><source/><target/></segment></unit>
            $sourceElement = $element->segment;
        }

        // Extract source (required)
        $source = (string)$sourceElement->source;
        if ($source === '') {
            throw new InvalidXliffException(
                sprintf('Missing required <source> element in unit "%s" at line %d', $id, $line),
                1700000005
            );
        }

        // Extract target (optional)
        $target = null;
        if (property_exists($sourceElement, 'target') && $sourceElement->target !== null && $sourceElement->target->getName() !== '') {
            $target = (string)$sourceElement->target;
        }

        return [
            'id' => $id,
            'source' => $source,
            'target' => $target,
            'line' => $line,
        ];
    }
}
