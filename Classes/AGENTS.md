<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2025-11-13 -->

# Classes/ — PHP Backend Code

High-performance XLIFF streaming parser implementation.

## 0. Skill Usage (Optional)

**Optional skills for enhanced code quality:**

```
TYPO3 Conformance Audits:
  Skill: Skill("netresearch-skills-bundle:typo3-conformance")
  When: Auditing extension quality, generating conformance reports
  Use: Evaluate adherence to TYPO3 12/13 standards, identify technical debt

TYPO3 Testing Setup:
  Skill: Skill("netresearch-skills-bundle:typo3-testing")
  When: Setting up test infrastructure, configuring PHPUnit
  Use: Create test configurations, manage fixtures, setup CI/CD
```

**Not required for normal development** - only invoke if performing comprehensive audits or test infrastructure setup.

## 1. Overview

This directory contains the core parser logic and exception handling:
- **Parser/XliffStreamingParser.php** - XMLReader-based streaming parser with Generator pattern
- **Exception/InvalidXliffException.php** - Exception for malformed XLIFF

**Design Goals:**
- Constant memory footprint (~30MB regardless of file size)
- 60x speed improvement over SimpleXML
- XXE attack protection (CWE-611)
- XLIFF 1.0, 1.2, 2.0 support

## 2. Setup & environment

### Prerequisites
- PHP 8.2, 8.3, or 8.4
- TYPO3 13.4+
- XMLReader extension (bundled with PHP)

### Installation
```bash
composer install
```

### DDEV (recommended)
```bash
ddev start  # Auto-runs composer install
ddev ssh    # Enter container
```

## 3. Build & tests

### File-scoped commands (run from project root)
```bash
# Syntax check
composer lint

# Auto-fix code style
composer fix

# Static analysis
composer analyse

# Run tests
composer test:unit
```

## 4. Code style & conventions

### Mandatory patterns
```php
<?php

declare(strict_types=1);

namespace Netresearch\NrXliffStreaming\Parser;

/**
 * Brief class description
 *
 * @author Netresearch DTT GmbH <info@netresearch.de>
 */
final class ClassName
{
    private const CONSTANT_NAME = 'value';

    public function methodName(string $param): \Generator
    {
        // Implementation
    }
}
```

### Rules
- **All classes MUST be `final`** (no inheritance)
- **`declare(strict_types=1)` mandatory** at top of every file
- **Complete PHPDoc blocks** with @author, @param, @return, @throws
- **Private constants** with UPPERCASE_NAMING
- **Type hints required** on all parameters and return types
- **4-space indentation** (PHP), 2-space (YAML/JSON)
- **UTF-8 encoding**, LF line endings

### Exception handling
```php
throw new InvalidXliffException(
    sprintf('Missing required "id" attribute in trans-unit at line %d', $line),
    1700000004  // Specific error code in 1700000001-1700000005 range
);
```

## 5. Security & safety

### XML parsing MUST use LIBXML_NONET
```php
// ✅ Correct - prevents XXE attacks
$reader->XML($xmlContent, 'UTF-8', LIBXML_NONET);

simplexml_load_string(
    $xml,
    \SimpleXMLElement::class,
    LIBXML_NONET  // Required!
);

// ❌ Wrong - vulnerable to XXE
$reader->XML($xmlContent);
```

### Protected against
- XXE (XML External Entity) attacks - CWE-611
- Billion Laughs attack (entity expansion DoS)
- SSRF via XXE (Server-Side Request Forgery)
- PHP wrapper attacks

### Validation rules
- Always validate required attributes (`id`)
- Always validate required elements (`<source>`)
- Include line numbers in error messages for debugging
- Fail fast with specific error codes

## 6. PR/commit checklist

Before committing code in Classes/:

- [ ] File starts with `<?php` and `declare(strict_types=1);`
- [ ] Class declared `final`
- [ ] Complete PHPDoc block with `@author Netresearch DTT GmbH`
- [ ] All methods have type hints (parameters + return)
- [ ] All public methods have PHPDoc
- [ ] Constants use `private const` with UPPERCASE
- [ ] XML parsing uses `LIBXML_NONET` flag
- [ ] Exception handling with specific error codes (1700000001-1700000005)
- [ ] Run: `composer lint && composer fix && composer analyse && composer test`

## 7. Good vs. bad examples

### ✅ Good: Generator pattern with constant memory
```php
public function parseTransUnits(string $xmlContent): \Generator
{
    $reader = new \XMLReader();

    if (!$reader->XML($xmlContent, 'UTF-8', LIBXML_NONET)) {
        throw new InvalidXliffException('Failed to parse XML', 1700000001);
    }

    while ($reader->read()) {
        if ($reader->nodeType === \XMLReader::ELEMENT
            && $reader->localName === 'trans-unit'
        ) {
            yield $this->extractTransUnit($reader);
        }
    }
}
```

### ❌ Bad: Loading entire file into memory
```php
// Don't do this - defeats the purpose of streaming!
public function parseTransUnits(string $xmlContent): array
{
    $xml = simplexml_load_string($xmlContent);
    $units = [];
    foreach ($xml->xpath('//trans-unit') as $unit) {
        $units[] = [...];
    }
    return $units;  // Entire file in memory
}
```

### ✅ Good: XXE protection
```php
simplexml_load_string(
    $xml,
    \SimpleXMLElement::class,
    LIBXML_NONET  // Prevents network access
);
```

### ❌ Bad: No XXE protection
```php
simplexml_load_string($xml);  // Vulnerable!
```

### ✅ Good: Specific error codes with context
```php
throw new InvalidXliffException(
    sprintf('Missing required "id" attribute in trans-unit at line %d', $line),
    1700000004
);
```

### ❌ Bad: Generic exceptions
```php
throw new \Exception('Invalid XLIFF');  // No context, no code
```

## 8. When stuck

### Resources
1. **Serena memories**: `list_memories()` - project_overview, code_style_conventions
2. **TYPO3 Coding Guidelines**: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/CodingGuidelines/
3. **XMLReader docs**: https://www.php.net/manual/en/book.xmlreader.php
4. **XLIFF spec**: https://docs.oasis-open.org/xliff/xliff-core/v1.2/xliff-core.html

### Common issues
- **Memory issues**: Ensure Generator pattern is used, not loading full file
- **XXE vulnerabilities**: Always use `LIBXML_NONET` flag
- **Type errors**: Add strict type hints everywhere
- **Style violations**: Run `composer fix` to auto-correct

### Error code ranges
- `1700000001`: XML parsing failure
- `1700000002`: Failed to read trans-unit
- `1700000003`: Invalid trans-unit XML
- `1700000004`: Missing required id attribute
- `1700000005`: Missing required source element

## 9. House Rules

### Performance requirements
- Memory usage MUST remain constant (~30MB) regardless of file size
- Processing time MUST scale linearly with file size
- Use `memory_get_peak_usage()` in tests to verify

### Immutability pattern
- All classes MUST be `final` (no exceptions)
- No inheritance hierarchies
- Composition over inheritance

### Generator pattern
- Use `yield` for streaming data
- Return `\Generator` with specific type hints
- Document generator type: `@return \Generator<array{id: string, source: string, target: string|null, line: int}>`

### Security non-negotiable
- `LIBXML_NONET` flag REQUIRED for all XML operations
- No user input without validation
- Fail fast with descriptive errors
