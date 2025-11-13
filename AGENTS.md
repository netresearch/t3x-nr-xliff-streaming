<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2025-11-13 -->

# AGENTS.md (root)

**Precedence:** The **closest AGENTS.md** to changed files wins. Root holds global defaults only.

## Global rules
- Keep PRs small (~≤300 net LOC)
- Conventional Commits: `type(scope): subject` (feat, fix, docs, test, refactor)
- Ask before: heavy deps, full e2e, repo rewrites
- Never commit secrets or PII
- All classes MUST be `final` with `declare(strict_types=1)`
- Security-first: Always use `LIBXML_NONET` for XML parsing

## Minimal pre-commit checks
- **Syntax**: `composer lint` - PHP syntax validation
- **Format**: `composer fix` - PHP-CS-Fixer (TYPO3 Coding Standards)
- **Analyze**: `composer analyse` - PHPStan level 2.0
- **Test**: `composer test` - PHPUnit 11 unit + functional tests

**All four MUST pass before committing.**

## Index of scoped AGENTS.md
- `./Classes/AGENTS.md` — PHP backend code (parser, exceptions)
- `./Tests/AGENTS.md` — PHPUnit testing guidelines
- `./Documentation/AGENTS.md` — RST documentation (⚠️ ALWAYS invoke typo3-docs skill)
- `./.ddev/AGENTS.md` — DDEV environment configuration (⚠️ ALWAYS invoke typo3-ddev skill)

## Available Skills (Context-Specific)

**Documentation (Documentation/):**
- ✅ **MANDATORY**: `Skill("netresearch-skills-bundle:typo3-docs")` - TYPO3 documentation standards
- Use: Before editing *.rst files, guides.xml, or README.md
- Provides: confval directives, card-grid navigation, validation scripts, README.md sync

**DDEV Environment (.ddev/):**
- ✅ **MANDATORY**: `Skill("netresearch-skills-bundle:typo3-ddev")` - DDEV environment setup
- Use: Setting up DDEV, modifying config.yaml, troubleshooting environment
- Provides: Multi-version TYPO3 testing, PHP version management, workflow optimization

**Code Quality (Classes/):**
- 🔍 **Optional**: `Skill("netresearch-skills-bundle:typo3-conformance")` - TYPO3 conformance audits
- Use: When auditing extension quality or generating conformance reports
- Provides: Standards evaluation, technical debt identification, quality scoring

**Test Infrastructure (Tests/):**
- 🔧 **Optional**: `Skill("netresearch-skills-bundle:typo3-testing")` - Test infrastructure setup
- Use: When setting up test frameworks or major test migrations
- Provides: PHPUnit configuration, fixture management, CI/CD integration

**Pattern:** Scoped AGENTS.md files indicate which skills apply to their directories.

## When instructions conflict
Nearest AGENTS.md wins. User prompts override files.
