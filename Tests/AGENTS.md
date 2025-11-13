<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2025-11-13 -->

# Tests/ — PHPUnit Testing Guidelines

Unit and functional tests for XLIFF streaming parser.

## 0. Skill Usage (Optional)

**Optional skill for test infrastructure setup:**

```
TYPO3 Testing Infrastructure:
  Skill: Skill("netresearch-skills-bundle:typo3-testing")
  When: Setting up new test infrastructure, configuring PHPUnit/CI
  Use: Create test configurations, manage fixtures, setup quality tooling

  Covers: PHPUnit 11/12, TYPO3 v12/v13, dependency injection testing,
          PHPStan level 10, Rector, php-cs-fixer integration
```

**Not required for writing tests** - only invoke if setting up test infrastructure from scratch or major test framework migrations.

## 1. Overview

This directory contains comprehensive test coverage:
- **Unit/Parser/XliffStreamingParserTest.php** - Functional tests (XLIFF 1.0, 1.2, 2.0)
- **Unit/Parser/XliffStreamingParserXXETest.php** - Security tests (XXE, DoS, SSRF)

**Test Goals:**
- 100% coverage of public methods
- Security vulnerability prevention validation
- Performance characteristic verification
- Edge case handling

## 2. Setup & environment

### Prerequisites
- PHPUnit 11.0+
- TYPO3 Testing Framework 8.0+
- PHP 8.2, 8.3, or 8.4

### Installation
```bash
composer install  # Installs dev dependencies
```

### DDEV
```bash
ddev start
ddev composer install
```

## 3. Build & tests

### File-scoped commands (run from project root)
```bash
# Run all unit tests
composer test:unit

# Run specific test file
.Build/bin/phpunit -c Build/phpunit/UnitTests.xml Tests/Unit/Parser/XliffStreamingParserTest.php

# Run specific test method
.Build/bin/phpunit -c Build/phpunit/UnitTests.xml --filter parsesValidXliff10WithoutNamespace

# Run with coverage (slow)
.Build/bin/phpunit -c Build/phpunit/UnitTests.xml --coverage-html .Build/coverage

# Run all tests (unit + functional)
composer test
```

## 4. Code style & conventions

### Test class structure
```php
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
 * @author Netresearch DTT GmbH <info@netresearch.de>
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
    public function testMethodDescription(): void
    {
        // Arrange
        // Act
        // Assert
    }
}
```

### Rules
- **Extend `UnitTestCase`** from TYPO3 Testing Framework
- **Use PHP 8+ attributes**: `#[Test]`, `#[CoversClass]`
- **No `test` prefix** in method names (handled by `#[Test]` attribute)
- **Return type `void`** on all test methods
- **setUp() MUST call parent::setUp()** first
- **Store subject in `$this->subject`** property
- **Use `self::assert*()` not `$this->assert*()`**

### Test method naming
```php
// ✅ Good - descriptive, no test prefix
#[Test]
public function parsesValidXliff10WithoutNamespace(): void

#[Test]
public function throwsExceptionOnMissingIdAttribute(): void

#[Test]
public function preventsXxeFileDisclosure(): void

// ❌ Bad - test prefix, unclear
#[Test]
public function testParse(): void

#[Test]
public function testException(): void
```

## 5. Security & safety

### Security test categories

**1. XXE (XML External Entity) Attacks**
```php
#[Test]
public function preventsXxeFileDisclosure(): void
{
    $xliff = <<<'XML'
<?xml version="1.0"?>
<!DOCTYPE foo [
  <!ENTITY xxe SYSTEM "file:///etc/passwd">
]>
<xliff><file><body><trans-unit id="1">
  <source>&xxe;</source>
</trans-unit></body></file></xliff>
XML;

    $units = iterator_to_array($this->subject->parseTransUnits($xliff));

    self::assertStringNotContainsString('root:', $units[0]['source']);
    self::assertStringNotContainsString('/etc/passwd', $units[0]['source']);
}
```

**2. Billion Laughs DoS Attack**
```php
#[Test]
public function preventsBillionLaughsAttack(): void
{
    $xliff = <<<'XML'
<?xml version="1.0"?>
<!DOCTYPE lolz [
  <!ENTITY lol "lol">
  <!ENTITY lol2 "&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;">
  <!ENTITY lol3 "&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;">
]>
<xliff><file><body><trans-unit id="1">
  <source>&lol3;</source>
</trans-unit></body></file></xliff>
XML;

    // Should either fail safely or complete quickly
    try {
        $units = iterator_to_array($this->subject->parseTransUnits($xliff));
        self::assertLessThan(100, strlen($units[0]['source']));
    } catch (\Exception $e) {
        self::assertTrue(true, 'Attack prevented parsing');
    }
}
```

**3. SSRF (Server-Side Request Forgery)**
```php
#[Test]
public function preventsSsrfViaXxe(): void
{
    $xliff = <<<'XML'
<?xml version="1.0"?>
<!DOCTYPE foo [
  <!ENTITY xxe SYSTEM "http://localhost/admin">
]>
<xliff><file><body><trans-unit id="1">
  <source>&xxe;</source>
</trans-unit></body></file></xliff>
XML;

    $units = iterator_to_array($this->subject->parseTransUnits($xliff));

    self::assertStringNotContainsString('admin', $units[0]['source']);
    self::assertStringNotContainsString('localhost', $units[0]['source']);
}
```

### All security tests MUST pass
No exceptions. These validate `LIBXML_NONET` protection.

## 6. PR/commit checklist

Before committing tests:

- [ ] Test class extends `TYPO3\TestingFramework\Core\Unit\UnitTestCase`
- [ ] Uses `#[CoversClass(ClassName::class)]` attribute
- [ ] Has `setUp()` method calling `parent::setUp()`
- [ ] Test methods use `#[Test]` attribute
- [ ] Test methods return `void`
- [ ] Uses `self::assert*()` methods
- [ ] Tests cover happy path, error cases, edge cases
- [ ] Security tests included for XML parsing code
- [ ] Test data uses heredoc syntax for readability
- [ ] Run: `composer test:unit` - all tests pass
- [ ] Run: `composer analyse` - no type errors in test code

## 7. Good vs. bad examples

### ✅ Good: Testing generators
```php
#[Test]
public function parsesMultipleTransUnits(): void
{
    $xliff = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.0">
    <file source-language="en" target-language="de">
        <body>
            <trans-unit id="first">
                <source>First</source>
                <target>Erste</target>
            </trans-unit>
            <trans-unit id="second">
                <source>Second</source>
                <target>Zweite</target>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

    // Convert generator to array for testing
    $units = iterator_to_array($this->subject->parseTransUnits($xliff));

    self::assertCount(2, $units);
    self::assertSame('first', $units[0]['id']);
    self::assertSame('second', $units[1]['id']);
}
```

### ❌ Bad: Not converting generator to array
```php
#[Test]
public function parsesTransUnits(): void
{
    $xliff = '...';

    $units = $this->subject->parseTransUnits($xliff);

    // Wrong! $units is a Generator, not an array
    self::assertCount(2, $units);  // Won't work
}
```

### ✅ Good: Exception testing
```php
#[Test]
public function throwsExceptionOnMissingIdAttribute(): void
{
    $this->expectException(InvalidXliffException::class);
    $this->expectExceptionCode(1700000004);

    $xliff = <<<'XML'
<?xml version="1.0"?>
<xliff><file><body><trans-unit>
  <source>Test</source>
</trans-unit></body></file></xliff>
XML;

    iterator_to_array($this->subject->parseTransUnits($xliff));
}
```

### ❌ Bad: Not using expectException
```php
#[Test]
public function throwsExceptionOnInvalidXml(): void
{
    try {
        $this->subject->parseTransUnits('invalid');
        self::fail('Should have thrown exception');  // Verbose
    } catch (InvalidXliffException $e) {
        self::assertTrue(true);
    }
}
```

### ✅ Good: Descriptive assertions
```php
self::assertStringNotContainsString(
    'root:',
    $units[0]['source'],
    'XXE entity should not be expanded'
);
```

### ❌ Bad: No assertion message
```php
self::assertStringNotContainsString('root:', $units[0]['source']);
```

## 8. When stuck

### Resources
1. **PHPUnit docs**: https://docs.phpunit.de/en/11.0/
2. **TYPO3 Testing Framework**: https://github.com/TYPO3/testing-framework
3. **Serena memory**: `read_memory("testing_guidelines")`
4. **PHPUnit attributes**: https://docs.phpunit.de/en/11.0/attributes.html

### Common issues
- **Generator not working**: Use `iterator_to_array()` to convert to array
- **Coverage errors**: Add `#[CoversClass(ClassName::class)]` attribute
- **setUp() errors**: Ensure `parent::setUp()` is called first
- **Assertion failures**: Check actual vs expected order in assertions

### Debugging tests
```bash
# Verbose output
.Build/bin/phpunit -c Build/phpunit/UnitTests.xml --verbose

# Stop on failure
.Build/bin/phpunit -c Build/phpunit/UnitTests.xml --stop-on-failure

# Debug specific test
.Build/bin/phpunit -c Build/phpunit/UnitTests.xml --filter testName --debug
```

## 9. House Rules

### Coverage requirements
- **100% coverage** of all public methods
- `#[CoversClass]` attribute REQUIRED on all test classes
- No `@codeCoverageIgnore` without justification

### Test organization
- Mirror `Classes/` directory structure
- One test class per source class
- Group related tests in same test class
- Security tests in dedicated test class

### Performance testing
```php
#[Test]
public function maintainsConstantMemoryUsage(): void
{
    // Test with different file sizes
    $small = $this->generateXliff(100);   // 100 trans-units
    $large = $this->generateXliff(10000); // 10,000 trans-units

    $memoryBefore = memory_get_peak_usage();
    iterator_to_array($this->subject->parseTransUnits($small));
    $memorySmall = memory_get_peak_usage() - $memoryBefore;

    $memoryBefore = memory_get_peak_usage();
    iterator_to_array($this->subject->parseTransUnits($large));
    $memoryLarge = memory_get_peak_usage() - $memoryBefore;

    // Memory usage should be roughly constant
    self::assertLessThan(
        $memorySmall * 2,
        $memoryLarge,
        'Memory usage should remain constant regardless of file size'
    );
}
```

### Test data management
- Use heredoc syntax for XLIFF test data
- Keep test XLIFF minimal but valid
- Document what each test validates
- Separate functional tests from security tests

### Never skip tests
- No `$this->markTestSkipped()` without issue reference
- No commenting out failing tests
- Fix tests immediately or create issue
