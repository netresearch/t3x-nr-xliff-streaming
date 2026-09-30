# Security assurance

This document argues why `nr_xliff_streaming` meets its security requirements. Every claim names the file, method or test that carries it. Methods without a file name are in `Classes/Parser/XliffStreamingParser.php`. For reporting a vulnerability see [SECURITY.md](../SECURITY.md).

## What the extension does, security-wise

The extension is a library. It ships one service, `XliffStreamingParser` (registered public in `Configuration/Services.yaml`), with one public method, `parseTransUnits(string $xmlContent): Generator`. The method reads an XLIFF 1.0, 1.2 or 2.0 document from a string and yields one `array{id, source, target, line}` per translation unit.

The extension has no backend module, no frontend plugin, no controller, no console command, no database table, no settings and no network or file access of its own. It never decides where the input comes from or what happens to the yielded units; the calling code does both.

## Security requirements

1. Parsing an untrusted document must not read local files, fetch network resources or load DTDs.
2. Parsing an untrusted document must not let entity expansion multiply the input or exhaust the process.
3. Invalid input must end in `InvalidXliffException` or in the end of iteration, never in a unit carrying content from outside the document or from an expanded entity declaration.
4. The parser's own working memory must not grow with the number of units.

## Security expectations

What a user can expect:

- No external entity, external DTD subset or external parameter entity is loaded, whatever its URI scheme (`file://`, `http://`, `php://`). The parser never passes `LIBXML_NOENT` or `LIBXML_DTDLOAD` (the `XMLReader::XML()` call in `parseTransUnits()` and the `simplexml_load_string()` call in `extractTransUnit()`), so libxml2 neither loads these resources nor substitutes entity references. Tests: `xxePayloadWithFileReadIsBlocked`, `xxePayloadWithPhpWrapperIsBlocked`, `externalDtdSubsetIsNotLoaded`, `externalParameterEntityIsNotLoaded` in `Tests/Unit/Parser/XliffStreamingParserXXETest.php`.
- No network request is made during parsing. Beyond the unloaded entities above, both calls pass `LIBXML_NONET`. Tests: `xxePayloadWithNetworkAccessIsBlocked`, `ssrfAttackViaXxeIsBlocked`.
- A unit that references any entity other than the five predefined XML entities is rejected with code 1700000003. `extractTransUnit()` reads each unit with `readOuterXml()` and parses it again on its own with `simplexml_load_string()`; that fragment has no DTD, so the reference cannot resolve.
- Entity expansion bombs do not expand. Entity references are not substituted, and libxml2 stops the parse at its entity amplification limit. Test: `billionLaughsAttackIsMitigated`.
- libxml2's default size limits apply, because the parser does not pass `LIBXML_PARSEHUGE`. A single text node above 10,000,000 bytes is rejected with code 1700000002. Test: `textNodeAboveLibxmlLimitIsRejected`.
- A unit without an `id` attribute or with an empty `<source>` is rejected with codes 1700000004 and 1700000005 (`extractTransUnit()`); empty input is rejected with code 1700000001 (`parseTransUnits()`, test `throwsInvalidXliffExceptionForEmptyInput` in `Tests/Unit/Parser/XliffStreamingParserEdgeCasesTest.php`).
- The reader is closed when iteration ends, also when the caller stops early or an exception is thrown (the `finally` block in `parseTransUnits()`).

What a user cannot expect:

- The parser does not bound the input size. The API takes the whole document as a string, so the caller holds it in memory; measured peak memory for a 55 MB document was the input size plus less than 1 MB. The parser's own working set is one unit at a time (`expand()` in `extractTransUnit()` builds one subtree). Callers that accept uploads limit the size before reading the content, as the upload example in `Documentation/Security/Index.rst` does.
- Malformed XML does not raise an exception. libxml2 reports it as an `E_WARNING` from `XMLReader::read()`, and the `while ($xmlReader->read())` loop in `parseTransUnits()` ends. The caller receives the units before the error, or none, and cannot tell a truncated document from a short one by the result alone (pinned by `handlesMalformedXmlGracefully` in `Tests/Unit/Parser/XliffStreamingParserTest.php`). The same applies when libxml2 stops at its entity amplification limit: depending on the libxml2 version the parser throws or ends without a unit. A caller that must detect this has to observe the warnings, for example with `set_error_handler()` around the iteration.
- The parser does not validate against the XLIFF schema and does not interpret the content. `source` and `target` are returned as text; escaping them for HTML, SQL or any other output is the caller's job.
- Exception messages contain values from the input (the unit `id` in the 1700000005 message) and libxml2 warnings contain the base URI, which is the working directory. Callers log them and show users a generic message, as `Documentation/Security/Index.rst` ("Error Handling") shows.
- The protection depends on libxml2 behaviour. It was measured with libxml2 2.9.14 (PHP 8.5.10 on the host) and 2.13.9 (the `ghcr.io/typo3/core-testing-php82` and `-php85` images that `Build/Scripts/runTests.sh` uses). A libxml2 build with different defaults, or a process-wide external entity loader installed with `libxml_set_external_entity_loader()`, is outside the extension's control; the XXE tests fail if the observed behaviour changes on the PHP that runs them.

## Threat model and trust boundaries

Actors:

- The integrating code (a TYPO3 extension or site package) that calls `parseTransUnits()`. It is trusted: it chooses the input and uses the output.
- The author of the XLIFF document. Untrusted: the document may come from a translation vendor, an upload or a repository the integrator does not control.

Trust boundary: the `$xmlContent` string passed to `parseTransUnits()`. Everything inside it is attacker-controlled. Everything the parser returns is data taken literally from that string.

Threats considered:

| Threat | Weakness | Countered by |
| --- | --- | --- |
| Reading local files through an external entity | CWE-611 | No `LIBXML_NOENT`/`LIBXML_DTDLOAD`; per-unit re-parse rejects entity references |
| Server-side request forgery through an entity or DTD URL | CWE-918 | Entities and DTDs are not loaded; `LIBXML_NONET` on both parser calls |
| Entity expansion (billion laughs, quadratic blowup) | CWE-776 | No substitution; libxml2 amplification limit; per-unit re-parse |
| Oversized text nodes | CWE-400 | libxml2 default limits (no `LIBXML_PARSEHUGE`) |
| Oversized documents | CWE-400 | Not countered by the parser: the caller bounds the input size |
| Uncaught error types from the XML API | CWE-248 | Empty input mapped to `InvalidXliffException` in `parseTransUnits()`; failed `expand()`/`readOuterXml()`/re-parse mapped to codes 1700000002 and 1700000003 |

Out of scope: how the integrating code obtains, stores or displays XLIFF content, and vulnerabilities in PHP, libxml2 or TYPO3 core.

## Secure design principles applied

- **Secure defaults, no configuration.** The flags are literals in the two parser calls; there is no setting that could enable entity loading (`ext_emconf.php`, no `ext_conf_template.txt`).
- **Least privilege.** The parser has no file, network or database access of its own and uses no TYPO3 API (`docs/ARCHITECTURE.md`, Dependency Rules).
- **Fail closed.** Any unit that cannot be read or parsed on its own raises `InvalidXliffException` instead of being returned partially (`extractTransUnit()`).
- **Economy of mechanism.** One final class of about 200 lines on top of PHP's `XMLReader` and `SimpleXML`; no parser of its own.
- **Contained side effects.** `libxml_use_internal_errors()` is restored and the libxml error buffer cleared after each re-parse in `extractTransUnit()`, so the parser does not change the caller's error handling.

## Countering common weaknesses

- **XML external entities (OWASP A05:2021, CWE-611), SSRF (A10:2021, CWE-918), entity expansion (CWE-776):** see the table above.
- **Injection and cross-site scripting (A03:2021, CWE-79, CWE-89):** the parser produces no HTML, SQL or shell command. It returns text; output encoding belongs to the caller.
- **Resource consumption (CWE-400):** per-unit processing and libxml2 limits bound the parser's work; the input size is the caller's limit.
- **Error message disclosure (CWE-209):** exceptions carry a code, the line number and the unit `id`, no file path; the caller decides what reaches users.
- **Vulnerable and outdated components (A06:2021):** the runtime dependencies are PHP's `libxml`, `SimpleXML` and `XMLReader` extensions and `typo3/cms-core` (`composer.json`). Composer Audit, Dependency Review and Renovate cover the Composer dependencies (README, "Governance and policies"); libxml2 is updated with the PHP build.

## Verification

- `Tests/Unit/Parser/XliffStreamingParserXXETest.php` holds the security tests named above. Adding `LIBXML_DTDLOAD | LIBXML_NOENT` to the `XMLReader::XML()` flags makes seven of them fail; adding `LIBXML_PARSEHUGE` makes `textNodeAboveLibxmlLimitIsRejected` fail.
- The unit suite runs on every pull request across PHP 8.2 to 8.5 and TYPO3 13.4 and 14.3 (`.github/workflows/ci.yml`), together with PHPStan at level `max` (`phpstan.neon`) and Opengrep (`.github/workflows/checks.yml`).
- Run it locally with `composer ci:test:php:unit` or `Build/Scripts/runTests.sh -s unit`.
