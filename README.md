<!-- SPDX-License-Identifier: GPL-2.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->
# XLIFF Streaming Parser for TYPO3

Streaming XLIFF parser for TYPO3. It reads XLIFF 1.0, 1.2 and 2.0 with XMLReader one translation unit at a time instead of building a SimpleXML tree of the whole document, which keeps the memory needed for large translation files low.

[![TYPO3 13](https://img.shields.io/badge/TYPO3-13-orange.svg)](https://get.typo3.org/version/13)
[![TYPO3 14](https://img.shields.io/badge/TYPO3-14-orange.svg)](https://get.typo3.org/version/14)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](LICENSE)

## Features

- **Low memory**: parsing adds about the size of the input to the process, where SimpleXML adds about eight times the size (see below)
- **One unit at a time**: only the current unit is built as a tree
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

Measured on PHP 8.5.10 with libxml2 2.9.14, generated XLIFF 1.2 documents. "Added memory" is the growth of the process's resident memory while parsing, on top of the input string the caller already holds.

| Input size | Units | Streaming added memory | SimpleXML added memory | Streaming time | SimpleXML time |
|-----------:|------:|-----------------------:|-----------------------:|---------------:|---------------:|
| 1.7 MB | 10,000 | 2.4 MB | 13.4 MB | 0.11 s | 0.01 s |
| 16.9 MB | 100,000 | 17.4 MB | 131.6 MB | 1.14 s | 0.14 s |
| 56.6 MB | 330,000 | 57.5 MB | 439.6 MB | 3.96 s | 0.42 s |

Memory is not independent of the file size: `parseTransUnits()` takes the whole document as a string, and libxml2 keeps its own copy of that buffer while parsing, so peak memory is about twice the input size plus a constant. PHP's `memory_get_peak_usage()` sees only the input string plus less than 1 MB, because libxml2 allocates outside PHP's memory manager. Streaming is slower than a single SimpleXML XPath query, because each unit is parsed a second time on its own; its advantage is memory. Earlier versions of this README claimed a constant 30 MB and a 60x speed-up (90 minutes against 90 seconds); nothing in this repository reproduces those figures, and the measurement above contradicts them, so they were removed.

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

The security assurance case, with the threat model, trust boundaries and what callers still have to do themselves (for example bound the input size), is in [docs/SECURITY-ASSURANCE.md](https://github.com/netresearch/t3x-nr-xliff-streaming/blob/main/docs/SECURITY-ASSURANCE.md).

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

Checks that run on pull requests in this repository:

- `.github/workflows/checks.yml`: Composer Audit (fails on an advisory for an installed package) and Opengrep SAST (which findings block is set organisation-wide, see [Static analysis (SAST)](https://github.com/netresearch/.github/blob/main/SECURITY.md#static-analysis-sast)), both through `typo3-ci-workflows`' `security.yml`; Dependency Review (fails on newly added dependencies with a vulnerability of severity high or higher); PHP License Audit (`license-check.yml`, fails on an SSPL or BSL licensed Composer dependency); CodeQL with `languages: auto`, which here analyses the workflow files only, because the repository contains no JavaScript or Go (PHPStan and Opengrep cover the PHP code); Betterleaks secret scanning; zizmor for the workflow files; the pull request quality check (`pr-quality`, on non-draft pull requests only: its Quality Gate job reports the size of the change and warns on large pull requests, its Auto-Approve job approves pull requests opened from this repository by authors GitHub associates with it as owner, member or collaborator); and the aggregate gate `All security checks`, which fails when one of these jobs fails or is cancelled. The fuzz job is skipped because the repository has no `Build/phpunit.xml`, the configuration the reusable `fuzz.yml` looks for.
- `.github/workflows/ci.yml`: PHP lint, code style (`.php-cs-fixer.php`), PHPStan (`phpstan.neon`) across the matrix, plus an advisory PHPStan pass against the PHPUnit the matrix resolves without the version cap (`PHPStan (unpinned PHPUnit)`), Rector, the unit suite across PHP 8.2 to 8.5 and TYPO3 13.4 and 14.3, the documentation rendering of `Documentation/`, and the aggregate gate `All CI checks`.
- `.github/workflows/harness-verify.yml`: `Build/Scripts/verify-harness.sh`.
- `.github/workflows/check-template-drift.yml`: the `.github/` files that the `typo3-extension` template in `netresearch/.github` manages, except those `.github/template.yaml` lists as intentional drift (`Template drift`).
- `.github/workflows/labeler.yml`: labels the pull request by the paths it changes.
- `.github/workflows/community.yml`: greets a contributor on their first pull request; `.github/workflows/auto-merge-deps.yml`: approves and enables auto-merge for Dependabot and Renovate pull requests that carry neither the `deps-no-automerge` nor the `deps-major` label, and is skipped for all others.

## Credits

Developed by [Netresearch DTT GmbH](https://www.netresearch.de)

## License

GPL-2.0-or-later. See [LICENSE](LICENSE) for details.
