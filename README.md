# TYPO3 Extension: XLIFF Streaming Parser

High-performance streaming XLIFF parser for TYPO3 supporting large translation files (10MB+) with constant memory footprint.

[![TYPO3 13](https://img.shields.io/badge/TYPO3-13-orange.svg)](https://get.typo3.org/version/13)
[![TYPO3 14](https://img.shields.io/badge/TYPO3-14-orange.svg)](https://get.typo3.org/version/14)
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

The security assurance case, with the threat model, trust boundaries and what callers still have to do themselves (for example bound the input size), is in [docs/SECURITY-ASSURANCE.md](docs/SECURITY-ASSURANCE.md).

## Requirements

- TYPO3 13.4 LTS or 14.3 LTS
- PHP 8.2, 8.3, 8.4, or 8.5

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

## Governance and policies

This extension follows the organisation-wide Netresearch policies:

- [Governance](https://github.com/netresearch/.github/blob/main/GOVERNANCE.md): ownership, roles, how decisions are made and conflicts resolved.
- [Roadmap](https://github.com/netresearch/.github/blob/main/ROADMAP.md): planned and excluded work for the next twelve months.
- [Handling of dependency and code analysis findings](https://github.com/netresearch/.github/blob/main/SECURITY.md#handling-of-dependency-and-code-analysis-findings): which vulnerability, licence and static-analysis findings must be fixed, by when, and how exceptions are recorded.
- [Secret management](https://github.com/netresearch/.github/blob/main/SECURITY.md#secret-management): where CI and release credentials are stored, who may use them, how committed secrets are detected, and when secrets are rotated.
- [Access roster](https://github.com/netresearch/.github/blob/main/docs/access-roster.md): the people and teams with administrative or write access to this repository.

Checks that run on every pull request in this repository:

- `.github/workflows/checks.yml`: Composer Audit (fails on an advisory for an installed package) and Opengrep SAST (fails on findings of severity WARNING or higher), both through `typo3-ci-workflows`' `security.yml`; Dependency Review (fails on newly added dependencies with a vulnerability of severity high or higher); PHP License Audit (`license-check.yml`, fails on an SSPL or BSL licensed Composer dependency); CodeQL with `languages: auto`, which here analyses the workflow files only, because the repository contains no JavaScript or Go (PHPStan and Opengrep cover the PHP code); Betterleaks secret scanning; zizmor for the workflow files; the pull request quality check. The fuzz job finds no fuzz suite in `Build/phpunit/` and is skipped.
- `.github/workflows/ci.yml`: PHP lint, code style (`.php-cs-fixer.php`), PHPStan (`phpstan.neon`), Rector, the unit suite across PHP 8.2 to 8.5 and TYPO3 13.4 and 14.3, and the documentation rendering of `Documentation/`.
- `.github/workflows/harness-verify.yml`: `Build/Scripts/verify-harness.sh`.
- `.github/workflows/check-template-drift.yml`: the `.github/` files that the `typo3-extension` template in `netresearch/.github` manages, except those `.github/template.yaml` lists as intentional drift.

## Credits

Developed by [Netresearch DTT GmbH](https://www.netresearch.de)

## License

GPL-2.0-or-later. See [LICENSE](LICENSE) for details.
