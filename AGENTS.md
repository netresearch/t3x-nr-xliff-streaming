<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-08-19 -->

# AGENTS.md (root)

**Precedence:** The **closest AGENTS.md** to changed files wins. Root holds global defaults only.

## Global rules
- Keep PRs small (~≤300 net LOC)
- Conventional Commits: `type(scope): subject` (feat, fix, docs, test, refactor)
- Ask before: heavy deps, full e2e, repo rewrites
- Never commit secrets or PII
- All classes MUST be `final` with `declare(strict_types=1)`
- Security-first: Always use `LIBXML_NONET` for XML parsing
- Supported PHP/TYPO3 versions: `composer.json` is authoritative; CI matrix lives in `.github/workflows/ci.yml`

## Commands
- **Style check**: `composer ci:test:php:cgl` — PHP-CS-Fixer dry-run (fix with `composer ci:cgl`)
- **Static analysis**: `composer ci:test:php:phpstan` — PHPStan, level `max` (see `phpstan.neon`)
- **Rector dry-run**: `composer ci:test:php:rector`
- **Unit tests**: `composer ci:test:php:unit` — PHPUnit 11
- **Functional tests**: `composer ci:test:php:functional`
- Make mirrors: `make cgl`, `make cgl-fix`, `make phpstan`, `make rector`, `make test-unit`, `make test-functional`, `make test`
- **Docs render**: `composer doc-make` (Docker-based TYPO3 render-guides)

Style check, PHPStan and unit tests MUST pass before committing.

## Index of scoped AGENTS.md
- `./Classes/AGENTS.md` — PHP backend code (parser, exceptions)
- `./Tests/AGENTS.md` — PHPUnit testing guidelines
- `./Documentation/AGENTS.md` — RST documentation (ALWAYS invoke the typo3-docs skill)
- `./.ddev/AGENTS.md` — DDEV environment configuration (ALWAYS invoke the typo3-ddev skill)

## Architecture & harness
- Component map: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- Execution plans: [docs/exec-plans/](docs/exec-plans/)
- Harness self-check: `bash Build/Scripts/verify-harness.sh` (CI: `.github/workflows/harness-verify.yml`)

## Available Skills (Context-Specific)
- **Documentation (`Documentation/`)** — MANDATORY: invoke the `typo3-docs` skill before editing `*.rst`, `guides.xml`, or README.md
- **DDEV (`.ddev/`)** — MANDATORY: invoke the `typo3-ddev` skill for config.yaml or environment work
- **Code quality (`Classes/`)** — optional: `typo3-conformance` skill for audits and conformance reports
- **Test infrastructure (`Tests/`)** — optional: `typo3-testing` skill for framework/CI setup

## When instructions conflict
Nearest AGENTS.md wins. User prompts override files.

## Commit Signing

Signed commits are required: `git commit -S --signoff`. The `require-signed-commits` ruleset on the default branch rejects unsigned commits at merge time, and the DCO check additionally requires the `Signed-off-by` trailer. Quickest setup is SSH signing — register your SSH key as a *signing key* on your GitHub account, then `git config --global gpg.format ssh && git config --global user.signingkey ~/.ssh/<key>.pub`.
