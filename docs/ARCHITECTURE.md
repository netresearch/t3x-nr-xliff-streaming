# Architecture

Agent-facing component map for `nr_xliff_streaming`. For user-facing documentation see `Documentation/` (rendered at https://docs.typo3.org/p/netresearch/nr-xliff-streaming/main/en-us/).

## System Overview

`nr_xliff_streaming` is a TYPO3 extension providing a high-performance streaming XLIFF parser for large translation files. It reads XLIFF 1.0, 1.2, and 2.0 content through PHP's `XMLReader` and yields trans-units one at a time via a `Generator`, keeping memory usage constant regardless of file size. All XML parsing uses `LIBXML_NONET` to block XXE/SSRF vectors.

## Components

| Component | Path | Role |
|-----------|------|------|
| Parser contract | `Classes/Parser/XliffParserInterface.php` | Interface: `parseTransUnits(string $xmlContent): Generator` |
| Streaming parser | `Classes/Parser/XliffStreamingParser.php` | `final` implementation using `XMLReader::XML(..., LIBXML_NONET)`; yields `array{id, source, target, line}` per trans-unit |
| Exception | `Classes/Exception/InvalidXliffException.php` | `final`, extends `RuntimeException`; error codes 1700000001-1700000005 |
| DI wiring | `Configuration/Services.yaml` | Autowire/autoconfigure for `Netresearch\NrXliffStreaming\`; `XliffStreamingParser` is `public: true` |
| Extension config | `ext_conf_template.txt`, `ext_emconf.php` | Extension settings and metadata (version lives in `ext_emconf.php`) |
| Test suites | `Tests/Unit/`, `Tests/Performance/` | PHPUnit unit tests (parser, XXE/security, edge cases, fixture-based integration) and benchmarks |
| Test configs | `Build/phpunit/UnitTests.xml`, `Build/phpunit/FunctionalTests.xml` | PHPUnit configurations (functional suite configured; `Tests/Functional/` not yet populated) |
| Test fixtures | `Tests/Unit/Fixtures/`, `Tests/Fixtures/` | Valid/invalid/security XLIFF samples |
| Build tooling | `Build/Scripts/runTests.sh`, `Build/scripts/generate-xliff-samples.php`, `Build/scripts/run-performance-benchmark.php`, `Build/fractor/fractor.php` | Test runner, sample generator, benchmark runner, Fractor config |
| Harness check | `Build/Scripts/verify-harness.sh` | Agent-harness consistency verification (CI: `.github/workflows/harness-verify.yml`) |

## Data Flow

1. Caller obtains `XliffStreamingParser` (via DI or `new`) and passes XLIFF content as a string to `parseTransUnits()`.
2. `XMLReader::XML($xmlContent, 'UTF-8', LIBXML_NONET)` opens a streaming cursor; malformed XML raises `InvalidXliffException` (1700000001).
3. The reader walks the document; each `trans-unit` (XLIFF 1.x) / segment (XLIFF 2.0) element is expanded to a `SimpleXMLElement` and validated (`id` attribute, `<source>` element required).
4. Each unit is yielded as `array{id: string, source: string, target: string|null, line: int}` — the caller iterates the `Generator`, so only one unit is materialized at a time.

## Dependency Rules

No `Tests/Architecture/` (phpat) suite exists; the following is derived from the code and DI config:

- `Classes/Parser/` depends only on `Classes/Exception/` and PHP built-ins (`XMLReader`, `SimpleXMLElement`, `Generator`) — no TYPO3 core APIs are used inside the parser.
- All classes are `final`; consumers program against `XliffParserInterface`.
- `Configuration/Services.yaml` registers everything under `Classes/` with autowiring; only the parser is public.

## Key Decisions

- Streaming via `XMLReader` + `Generator` instead of `SimpleXML` for constant memory: rationale and measurements in `PERFORMANCE_BENCHMARK.md` and `Documentation/Performance/`.
- Security posture (`LIBXML_NONET`, XXE/Billion-Laughs/SSRF protection): `SECURITY.md`, `Documentation/Security/`, enforced by `Tests/Unit/Parser/XliffStreamingParserXXETest.php`.
- Coding and testing conventions: `AGENTS.md` (root) and the scoped `AGENTS.md` files in `Classes/`, `Tests/`, `Documentation/`, `.ddev/`.
