<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2025-11-13 -->

# Documentation/ — RST Documentation

TYPO3 extension documentation in reStructuredText (RST) format.

## 0. Skill Usage (TYPO3 Documentation)

**ALWAYS invoke the typo3-docs skill when working in this directory:**

```
When to invoke: Before editing any *.rst files, guides.xml, or README.md
Command: Skill("netresearch-skills-bundle:typo3-docs")
Purpose: Loads TYPO3-specific directives, validation tools, and modern standards
```

**The skill provides:**
- ✅ TYPO3-specific directives (confval, versionadded, card-grid, php:method)
- ✅ Modern standards (guides.xml over Settings.cfg, card-grid navigation)
- ✅ Validation scripts (validate_docs.sh, render_docs.sh)
- ✅ Documentation synchronization rules (README.md ↔ Documentation/)
- ✅ Intercept deployment guidance (automatic publishing to docs.typo3.org)

**Workflow pattern:**
1. Invoke typo3-docs skill (`Skill("netresearch-skills-bundle:typo3-docs")`)
2. Follow skill guidance for TYPO3-specific features
3. Validate: `scripts/validate_docs.sh` or render locally
4. Ensure README.md and Documentation/ stay synchronized
5. Commit both together in atomic commits

## 1. Overview

This directory contains user-facing documentation for the extension:
- **Index.rst** - Main documentation index (use card-grid navigation)
- **Introduction/** - What this extension does
- **Installation/** - How to install via Composer
- **Integration/** - Usage examples and API reference
- **API/** - API documentation and method signatures
- **Performance/** - Performance characteristics and benchmarks
- **Security/** - Security features and protection mechanisms
- **guides.xml** - TYPO3 documentation configuration (MODERN - preferred)

**Documentation Goals:**
- Clear installation instructions
- Practical usage examples
- API reference for developers
- Performance and security information

**Modern TYPO3 13.x Standards:**
- ✅ Use `guides.xml` (modern PHP-based rendering)
- ✅ Use `confval` directive for ALL configuration (mandatory)
- ✅ Use `card-grid` navigation in Index.rst (modern default)
- ✅ Include UTF-8 emoji icons in card titles (📘 🔧 ⚡)
- ✅ Add `stretched-link` class to card footers
- ❌ Don't use Settings.cfg (legacy Sphinx - migrate to guides.xml)
- ❌ Don't use plain text for configuration (must use confval)

## 2. Setup & environment

### Prerequisites
- Basic understanding of reStructuredText (RST) syntax
- Text editor with RST support (VS Code + extension recommended)
- **TYPO3 docs skill** for modern TYPO3-specific directives

### Validation & Rendering Scripts

**Validate documentation:**
```bash
# If scripts/validate_docs.sh exists (recommended)
scripts/validate_docs.sh

# Otherwise use manual validation
# - Check RST syntax with VS Code extension
# - Verify guides.xml structure
# - Check for broken cross-references
```

**Render locally:**
```bash
# If scripts/render_docs.sh exists (recommended)
scripts/render_docs.sh

# Otherwise use TYPO3 renderer manually
composer global require t3docs/render-guides
render-guides Documentation/
```

**View rendered output:**
```bash
open Documentation-GENERATED-temp/Index.html
# or
xdg-open Documentation-GENERATED-temp/Index.html
```

### Official rendering
Documentation is automatically rendered at:
https://docs.typo3.org/p/netresearch/nr-xliff-streaming/main/en-us/

**Automatic deployment:**
- Webhook to TYPO3 Intercept on git push
- Builds triggered by main/master commits and version tags
- First build requires manual approval (1-3 business days)
- See typo3-docs skill for complete deployment guide

## 3. Build & tests

### File-scoped commands (run from project root)
```bash
# Validate RST syntax (if script available)
scripts/validate_docs.sh

# Render documentation locally (if script available)
scripts/render_docs.sh

# Validate and render in one step
scripts/validate_docs.sh && scripts/render_docs.sh

# Lint project (basic syntax check)
composer lint
```

**ALWAYS validate and render locally before committing!**

## 4. Code style & conventions

### RST file structure
```rst
.. include:: /Includes.rst.txt

==============
Section Title
==============

Introduction paragraph.

Subsection Title
================

Content here.

Code Examples
-------------

.. code-block:: php

   <?php
   // PHP code with syntax highlighting
   $parser = new XliffStreamingParser();
```

### Basic RST Rules
- **Start with `.. include:: /Includes.rst.txt`** in all RST files
- **Title underlines** must match title length exactly
- **Hierarchy**: `====` (top), `----` (subsection), `~~~~` (sub-subsection)
- **Code blocks** must specify language: `.. code-block:: php`
- **Blank line** after directives (.. code-block::, .. note::, etc.)
- **Indentation**: 3 spaces for directive content

### TYPO3-Specific Directives (MANDATORY)

**Configuration values (confval) - REQUIRED for all config:**
```rst
.. confval:: settingName

   :type: boolean
   :Default: true
   :Path: $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['nr_xliff_streaming']['settingName']

   Description of what this setting does and when to use it.

   Example
   -------

   .. code-block:: php
      :caption: ext_localconf.php

      $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['nr_xliff_streaming']['settingName'] = false;
```

**Version directives - REQUIRED for version-specific features:**
```rst
.. versionadded:: 1.1.0
   The parser now supports XLIFF 2.0 format.

.. versionchanged:: 1.2.0
   Streaming performance improved by 20%.

.. deprecated:: 1.3.0
   The ``oldSetting`` option is deprecated. Use ``newSetting`` instead.
   Will be removed in version 2.0.0.
```

**PHP API documentation - REQUIRED for public methods:**
```rst
.. php:method:: parseTransUnits(string $xmlContent): \Generator

   Parses XLIFF trans-units using streaming XMLReader.

   :param string $xmlContent: XLIFF file content
   :returns: Generator yielding trans-unit arrays
   :returntype: ``\Generator<array{id: string, source: string, target: string|null, line: int}>``
   :throws \\Netresearch\\NrXliffStreaming\\Exception\\InvalidXliffException: On malformed XML
```

**Card-grid navigation - DEFAULT for Index.rst:**
```rst
.. toctree::
   :hidden:
   :maxdepth: 2

   Introduction/Index
   Installation/Index

.. card-grid::
   :columns: 1
   :columns-md: 2
   :gap: 4
   :card-height: 100

   .. card:: 📘 Introduction

      Learn what the extension does and key features.

      .. card-footer:: :ref:`Read more <introduction>`
         :button-style: btn btn-primary stretched-link

   .. card:: 🔧 Installation

      Installation instructions via Composer.

      .. card-footer:: :ref:`Read more <installation>`
         :button-style: btn btn-secondary stretched-link
```

**Critical:** Always include `stretched-link` class in card-footer for full card clickability!

### Title hierarchy
```rst
==============
Main Title (Index.rst only)
==============

Section Title
=============

Subsection Title
----------------

Sub-subsection Title
~~~~~~~~~~~~~~~~~~~~
```

### Code blocks
```rst
.. code-block:: php

   <?php

   declare(strict_types=1);

   use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;

   $parser = new XliffStreamingParser();
   $xliffContent = file_get_contents('translation.xlf');

   foreach ($parser->parseTransUnits($xliffContent) as $unit) {
       echo sprintf(
           "ID: %s\nSource: %s\nTarget: %s\n\n",
           $unit['id'],
           $unit['source'],
           $unit['target'] ?? '(untranslated)'
       );
   }
```

### Admonitions
```rst
.. note::
   This is a note with important information.

.. warning::
   This is a warning about potential issues.

.. important::
   This is critical information users must know.

.. tip::
   This is a helpful tip for better usage.
```

## 5. Security & safety

### Security documentation requirements
- **Always document security features** (XXE protection, DoS prevention)
- **Include vulnerability references** (CWE numbers)
- **Provide secure usage examples**
- **Warn about insecure patterns**

### Example security documentation
```rst
Security
========

This extension provides built-in protection against:

- **XXE (XML External Entity) Attacks** - CWE-611
- **Billion Laughs Attack** - Entity expansion DoS
- **SSRF via XXE** - Server-Side Request Forgery

All XML parsing uses ``LIBXML_NONET`` flag to prevent network access during parsing.

.. warning::
   Never disable ``LIBXML_NONET`` flag when parsing untrusted XML content.
```

### Documentation Synchronization with README.md

**CRITICAL RULE:** README.md and Documentation/ must stay synchronized!

**What must match:**
- Installation instructions (README.md ↔ Documentation/Installation/)
- Feature descriptions (README.md ↔ Documentation/Index.rst)
- Configuration examples (README.md ↔ Documentation/Integration/)
- Code examples (button names, method signatures, defaults)
- Version numbers (badges ↔ guides.xml)

**Synchronization workflow:**
1. ✅ Update README.md with changes
2. ✅ Update corresponding Documentation/*.rst files
3. ✅ Verify code examples match Classes/ implementation
4. ✅ Commit both in same atomic commit
5. ❌ NEVER update one without the other

**Example synchronization issue:**
```markdown
# README.md
toolbar: [typo3image]  # Wrong!

# Documentation/Integration/Index.rst
toolbar: [typo3image]  # Wrong!

# Classes/Controller/ImageController.php (source of truth)
editor.ui.componentFactory.add('insertimage', ...)  # Correct!
```

**Fix:** Update both README.md AND Documentation/ with correct value from code.

## 6. PR/commit checklist

Before committing documentation:

### RST Syntax Checks
- [ ] All RST files start with `.. include:: /Includes.rst.txt`
- [ ] Title underlines match title length exactly
- [ ] Code blocks specify language (php, bash, yaml, etc.)
- [ ] Blank lines after all directives
- [ ] 3-space indentation for directive content
- [ ] No trailing whitespace

### TYPO3-Specific Checks
- [ ] Configuration uses `confval` directive (not plain text)
- [ ] Version-specific features use `versionadded`/`versionchanged`
- [ ] PHP methods documented with `php:method` directive
- [ ] Card-grid navigation uses `stretched-link` class
- [ ] UTF-8 emoji icons in card titles (📘 🔧 ⚡ 🛡️)
- [ ] guides.xml exists (not Settings.cfg)

### Validation & Synchronization
- [ ] Run: `scripts/validate_docs.sh` (if available)
- [ ] Run: `scripts/render_docs.sh` - verify no warnings
- [ ] Verify README.md matches Documentation/ content
- [ ] Code examples match Classes/ implementation
- [ ] guides.xml includes all new RST files
- [ ] No broken cross-references in rendered output

### Quality Gates
- [ ] Invoke typo3-docs skill before starting
- [ ] Follow skill guidance for TYPO3-specific features
- [ ] Render locally and check for warnings
- [ ] Commit README.md and Documentation/ together

## 7. Good vs. bad examples

### ✅ Good: confval directive (MANDATORY)
```rst
.. confval:: fetchExternalImages

   :type: boolean
   :Default: true
   :Path: $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['nr_xliff_streaming']['fetchExternalImages']

   Controls whether external image URLs are fetched automatically.

   Example
   -------

   .. code-block:: php
      :caption: ext_localconf.php

      $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['nr_xliff_streaming']['fetchExternalImages'] = false;
```

### ❌ Bad: Plain text configuration
```rst
fetchExternalImages
~~~~~~~~~~~~~~~~~~~
Type: boolean
Default: true
```

### ✅ Good: Card-grid with stretched-link
```rst
.. card:: 📘 Introduction

   Learn what the extension does.

   .. card-footer:: :ref:`Read more <introduction>`
      :button-style: btn btn-primary stretched-link
```

### ❌ Bad: Missing stretched-link
```rst
.. card:: Introduction

   Learn what the extension does.

   .. card-footer:: :ref:`Read more <introduction>`
      :button-style: btn btn-primary
```

### ✅ Good: Version directive
```rst
.. versionadded:: 1.1.0
   Support for XLIFF 2.0 format.
```

### ❌ Bad: Plain text version info
```rst
Since version 1.1.0: Support for XLIFF 2.0 format.
```

### ✅ Good: Complete code example
```rst
Basic Usage
===========

Install via Composer:

.. code-block:: bash

   composer require netresearch/nr-xliff-streaming

Example usage:

.. code-block:: php

   <?php

   declare(strict_types=1);

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

### ❌ Bad: Incomplete example without context
```rst
Usage
=====

.. code-block:: php

   $parser->parseTransUnits($xliff);
```

### ✅ Good: Title hierarchy
```rst
Installation
============

Prerequisites
-------------

- TYPO3 13.4+
- PHP 8.2, 8.3, or 8.4

Composer Installation
---------------------

Install via Composer:

.. code-block:: bash

   composer require netresearch/nr-xliff-streaming
```

### ❌ Bad: Inconsistent underlines
```rst
Installation
========

Prerequisites
---

Composer Installation
---------
```

### ✅ Good: Tables with performance data
```rst
Performance Comparison
======================

.. table:: Memory and Speed Comparison

   ==========  ==================  ================  ==============  ==============
   File Size   SimpleXML Memory    Streaming Memory  SimpleXML Time  Streaming Time
   ==========  ==================  ================  ==============  ==============
   1 MB        8 MB                30 MB             0.5s            0.1s
   10 MB       80 MB               30 MB             5s              0.5s
   100 MB      800 MB              30 MB             90min           90s
   ==========  ==================  ================  ==============  ==============
```

### ❌ Bad: Performance data without structure
```rst
Performance: 1MB file uses 8MB with SimpleXML but only 30MB streaming.
10MB file uses 80MB vs 30MB. 100MB file uses 800MB vs 30MB.
```

## 8. When stuck

### Invoke typo3-docs skill FIRST
```
Skill("netresearch-skills-bundle:typo3-docs")
```
The skill provides comprehensive guidance for all TYPO3-specific documentation needs.

### Resources
1. **TYPO3 docs skill** - Complete TYPO3 documentation guidance
2. **TYPO3 Documentation Guide**: https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/
3. **RST Primer**: https://www.sphinx-doc.org/en/master/usage/restructuredtext/basics.html
4. **Online RST Editor**: https://rst.ninjs.org/
5. **VS Code Extension**: reStructuredText (LeXtudio Inc.)

### Common issues
- **confval not rendering**: Check `:type:`, `:Default:`, `:Path:` all present
- **Card not clickable**: Add `stretched-link` class to card-footer
- **Version info not styled**: Use `.. versionadded::` not plain text
- **Title underline wrong length**: Must exactly match title length
- **Code block not rendering**: Check blank line after `.. code-block::`
- **Links broken**: Use relative paths from Documentation/ directory
- **Indentation errors**: Use 3 spaces for directive content
- **README.md out of sync**: Update both README.md and Documentation/ together

### RST syntax quick reference
```rst
**bold text**
*italic text*
``inline code``
`external link <https://example.com>`__
:ref:`internal-reference`
:php:`ClassName` (domain role)
```

## 9. House Rules

### Modern TYPO3 13.x Standards (NON-NEGOTIABLE)

**1. guides.xml (REQUIRED - Modern PHP-Based)**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<guides xmlns="https://www.phpdoc.org/guides"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="https://www.phpdoc.org/guides https://www.phpdoc.org/guides/guides.xsd"
>
    <project title="XLIFF Streaming Parser" version="1.0.0"/>
    <extension class="\T3Docs\Typo3DocsTheme\DependencyInjection\Typo3DocsThemeExtension"
               edit-on-github="netresearch/t3x-nr-xliff-streaming"
               edit-on-github-branch="main"
    />
</guides>
```
**DO NOT use Settings.cfg** (legacy Sphinx-based - deprecated)

**2. confval Directive (MANDATORY for Configuration)**

Always use confval, never plain text:
```rst
✅ Correct:
.. confval:: settingName
   :type: boolean
   :Default: true

❌ Wrong:
settingName: boolean, default: true
```

**3. Card-Grid Navigation (DEFAULT for Index.rst)**

Always use card-grid with stretched-link:
```rst
✅ Correct:
.. card-footer:: :ref:`Read more <link>`
   :button-style: btn btn-primary stretched-link

❌ Wrong:
.. card-footer:: :ref:`Read more <link>`
   :button-style: btn btn-primary
```

**4. UTF-8 Emoji Icons (REQUIRED in Cards)**

Use descriptive emoji in card titles:
- 📘 Introduction/Overview
- 🔧 Configuration/Installation
- ⚡ Performance
- 🛡️ Security
- 🎨 Features
- 🔍 API Reference

### Documentation structure
```
Documentation/
├── Index.rst                 # Main index (card-grid navigation)
├── Includes.rst.txt          # Shared includes
├── guides.xml               # Configuration (MODERN - required)
├── Introduction/
│   └── Index.rst            # What this extension does
├── Installation/
│   └── Index.rst            # Installation instructions
├── Integration/
│   └── Index.rst            # Usage examples + confval directives
├── API/
│   └── Index.rst            # API reference (php:method directives)
├── Performance/
│   └── Index.rst            # Performance benchmarks
└── Security/
    └── Index.rst            # Security features
```

### Update guides.xml when adding files
```xml
<?xml version="1.0" encoding="UTF-8"?>
<guides xmlns="https://guides.typo3.org/ns/guides">
    <project>
        <title>XLIFF Streaming Parser</title>
    </project>
    <inventory>
        <document key="introduction">Introduction/Index.rst</document>
        <document key="installation">Installation/Index.rst</document>
        <document key="integration">Integration/Index.rst</document>
        <document key="api">API/Index.rst</document>
        <document key="performance">Performance/Index.rst</document>
        <document key="security">Security/Index.rst</document>
    </inventory>
</guides>
```

### Include real code examples
- All code examples must be working, tested code
- Use actual API from Classes/ directory
- Include complete examples, not fragments
- Show both basic and advanced usage
- **Verify against source code** (Classes/) for accuracy

### Keep in sync with code AND README.md
- Update API documentation when signatures change
- Update performance data when benchmarks change
- Update security documentation when protections change
- **Update README.md when Documentation/ changes** (atomic commits)
- **Update Documentation/ when README.md changes** (atomic commits)
- Review documentation with every major release

### Validation is MANDATORY
- Run `scripts/validate_docs.sh` before committing
- Render locally with `scripts/render_docs.sh`
- Check for rendering warnings
- Verify README.md synchronization
- No broken cross-references
- All TYPO3 directives properly formed
