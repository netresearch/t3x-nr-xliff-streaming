<?php

declare(strict_types=1);

namespace Netresearch\NrXliffStreaming\Parser;

use Netresearch\NrXliffStreaming\Exception\InvalidXliffException;

/**
 * Interface for XLIFF parsers
 *
 * Defines the contract for parsing XLIFF translation files.
 * Implementations may use different parsing strategies (streaming, DOM, etc.)
 *
 * @author Netresearch DTT GmbH
 */
interface XliffParserInterface
{
    /**
     * Parse XLIFF trans-units from XML content
     *
     * Returns a generator that yields translation units one at a time.
     * Each unit contains id, source, target (optional), and line number.
     *
     * @param string $xmlContent XLIFF file content
     * @return \Generator<array{id: string, source: string, target: string|null, line: int}>
     * @throws InvalidXliffException if XML is malformed or invalid XLIFF structure
     */
    public function parseTransUnits(string $xmlContent): \Generator;
}
