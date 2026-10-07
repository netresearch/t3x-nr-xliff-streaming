<?php

declare(strict_types=1);

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

namespace Netresearch\NrXliffStreaming\Parser;

use DOMNode;
use Generator;
use Netresearch\NrXliffStreaming\Exception\InvalidXliffException;
use SimpleXMLElement;

use XMLReader;

use function is_string;

/**
 * High-performance streaming XLIFF parser supporting XLIFF 1.0, 1.2, and 2.0
 *
 * Uses XMLReader and builds a tree for one translation unit at a time. The
 * document itself is passed as a string, so memory grows with the input size;
 * see Documentation/Performance for measurements.
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
     * Generator pattern yields one trans-unit at a time.
     * Each trans-unit is converted to SimpleXMLElement for easy data extraction.
     *
     * Memory: parsing adds about the input size (libxml2's copy of the buffer),
     * against about eight times the input size for a SimpleXML tree.
     *
     * @param string $xmlContent XLIFF file content
     * @return Generator<array{id: string, source: string, target: string|null, line: int}>
     * @throws InvalidXliffException if XML is malformed or invalid XLIFF structure
     */
    public function parseTransUnits(string $xmlContent): Generator
    {
        // XMLReader::XML() throws a ValueError for an empty string, which is
        // not part of this method's contract.
        if ($xmlContent === '') {
            throw new InvalidXliffException('Failed to parse XML content: input is empty', 1700000001);
        }

        // XMLReader::XML() is static as of PHP 8.0 and returns the reader it
        // set up, so take it from the return value instead of calling it on a
        // separately constructed instance.
        $xmlReader = XMLReader::XML($xmlContent, 'UTF-8', LIBXML_NONET);

        if (!$xmlReader instanceof XMLReader) {
            throw new InvalidXliffException('Failed to parse XML content', 1700000001);
        }

        try {
            // Stream through XML elements
            while ($this->read($xmlReader)) {
                // Check for trans-unit elements (XLIFF 1.x) or unit elements (XLIFF 2.0)
                if (
                    $xmlReader->nodeType === XMLReader::ELEMENT
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
     * Advance the reader by one node and fail on malformed XML
     *
     * XMLReader::read() reports a well-formedness error as a PHP warning and
     * returns false, so the loop above would end as if the document were
     * complete: the caller got the units before the error, or none, and could
     * not tell a truncated or broken document from a short one.
     *
     * @throws InvalidXliffException if the XML is not well-formed
     */
    private function read(XMLReader $xmlReader): bool
    {
        return $this->failOnXmlError(static fn(): bool => $xmlReader->read());
    }

    /**
     * Run one XMLReader call and raise its libxml2 errors as an exception
     *
     * The libxml2 errors of this one call are collected instead of being
     * emitted as PHP warnings. A fatal error, or an error with which the call
     * failed, raises InvalidXliffException. XMLReader's own warning for a failed call
     * ("An Error Occurred while expanding") is suppressed for the same reason:
     * the failure is reported by the exception, here or by the caller. The
     * caller's libxml error setting and error handler are restored before
     * control returns, also between the yielded units.
     *
     * @template T
     * @param callable(): T $call
     * @return T
     * @throws InvalidXliffException if libxml2 reported a fatal error, or an error with which the call failed
     */
    private function failOnXmlError(callable $call): mixed
    {
        $useInternalErrors = libxml_use_internal_errors(true);
        $errorsBefore = count(libxml_get_errors());
        set_error_handler(static fn(): bool => true, E_WARNING);

        try {
            $result = $call();
            $errors = array_slice(libxml_get_errors(), $errorsBefore);

            // With the caller's internal-error collection on, the errors of
            // a call that went on stay buffered and the next call would copy
            // them again, so the cost of a document with many such errors
            // outside its units would grow quadratically. Drop what this
            // call added, unless the caller had errors pending.
            if ($errorsBefore === 0) {
                libxml_clear_errors();
            }
        } finally {
            restore_error_handler();
            libxml_use_internal_errors($useInternalErrors);
        }

        foreach ($errors as $error) {
            // A fatal error ends the document, and an error with which the
            // call failed (libxml2 2.9 reports an over-long text node so from
            // expand()) ends the unit. Errors the reader goes on after, such
            // as an undeclared namespace prefix, keep the document parsing as
            // it did before.
            if ($error->level === LIBXML_ERR_FATAL || ($result === false && $error->level === LIBXML_ERR_ERROR)) {
                throw new InvalidXliffException(
                    sprintf('Malformed XML at line %d: %s', $error->line, trim($error->message)),
                    1700000001
                );
            }
        }

        return $result;
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
        return in_array($uri, [null, '', self::XLIFF_1_2_NS, self::XLIFF_2_0_NS], true);
    }

    /**
     * Extract trans-unit data from current XMLReader position
     *
     * Converts XMLReader node to SimpleXMLElement for easy data extraction
     * with XXE protection (LIBXML_NONET flag).
     *
     * @param XMLReader $xmlReader XMLReader positioned at trans-unit element
     * @return array{id: string, source: string, target: string|null, line: int}
     * @throws InvalidXliffException if trans-unit structure is invalid
     */
    private function extractTransUnit(XMLReader $xmlReader): array
    {
        $expanded = $this->failOnXmlError(static fn(): DOMNode|false => $xmlReader->expand());
        if ($expanded === false) {
            throw new InvalidXliffException(
                // Entity amplification and a text node above libxml2's limit
                // make libxml2 report an error, which failOnXmlError() turns
                // into 1700000001; this is a failure libxml2 gave no reason
                // for.
                'Failed to expand trans-unit',
                1700000002
            );
        }

        $line = $expanded->getLineNo();

        // Read trans-unit as XML string
        // Note: readOuterXml() can return false in practice despite PHPDoc saying string
        $xml = $xmlReader->readOuterXml();
        if (!is_string($xml) || $xml === '') {
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
                SimpleXMLElement::class,
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
