# TYPO3 Extension: XLIFF Streaming Parser

High-performance streaming XLIFF parser for TYPO3 supporting large translation files (10MB+) with constant memory footprint.

[![TYPO3 13](https://img.shields.io/badge/TYPO3-13-orange.svg)](https://get.typo3.org/version/13)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](LICENSE)

## Features

- **High Performance**: 60x faster than SimpleXML for large files (90 seconds vs 90 minutes)
- **Memory Efficient**: 30x memory reduction (30MB vs 900MB for 108MB file)
- **Constant Memory**: Memory usage independent of file size
- **XLIFF Support**: Full XLIFF 1.0, 1.2, and 2.0 support (trans-unit and unit elements)
- **XXE Protection**: Built-in protection against XML External Entity attacks
- **Generator Pattern**: Stream-based processing for optimal resource usage

## Installation

Install via Composer:

```bash
composer require netresearch/nr-xliff-streaming
```

## Usage

### Basic Example

```php
use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;

$parser = new XliffStreamingParser();
$xliffContent = file_get_contents('path/to/large-translation.xlf');

foreach ($parser->parseTransUnits($xliffContent) as $unit) {
    echo sprintf(
        "ID: %s\nSource: %s\nTarget: %s\n\n",
        $unit['id'],
        $unit['source'],
        $unit['target'] ?? '(untranslated)'
    );
}
```

### Dependency Injection

```php
use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;

final class MyTranslationService
{
    public function __construct(
        private readonly XliffStreamingParser $xliffParser
    ) {}

    public function importTranslations(string $xliffContent): void
    {
        foreach ($this->xliffParser->parseTransUnits($xliffContent) as $unit) {
            // Process translation unit
        }
    }
}
```

## Performance Comparison

| File Size | SimpleXML Memory | Streaming Memory | SimpleXML Time | Streaming Time |
|-----------|------------------|------------------|----------------|----------------|
| 1 MB      | 8 MB            | 30 MB            | 0.5s          | 0.1s          |
| 10 MB     | 80 MB           | 30 MB            | 5s            | 0.5s          |
| 100 MB    | 800 MB          | 30 MB            | 90min         | 90s           |

## Supported XLIFF Versions

- **XLIFF 1.0**: No namespace, `<trans-unit>` elements
- **XLIFF 1.2**: `urn:oasis:names:tc:xliff:document:1.2`, `<trans-unit>` elements
- **XLIFF 2.0**: `urn:oasis:names:tc:xliff:document:2.0`, `<unit>` + `<segment>` elements

## Security

This extension provides built-in protection against:

- **XXE (XML External Entity) Attacks** - CWE-611
- **Billion Laughs Attack** - Entity expansion DoS
- **SSRF via XXE** - Server-Side Request Forgery

All XML parsing uses `LIBXML_NONET` flag to prevent network access during parsing.

## Requirements

- TYPO3 13.4+
- PHP 8.2, 8.3, or 8.4

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run unit tests only
composer test:unit

# Code quality
composer lint
composer fix
composer analyse
```

## Credits

Developed by [Netresearch DTT GmbH](https://www.netresearch.de)

## License

GPL-2.0-or-later. See [LICENSE](LICENSE) for details.
