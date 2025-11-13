.. include:: /Includes.rst.txt

.. _troubleshooting:

===============
Troubleshooting
===============

This guide helps you resolve common issues with the XLIFF Streaming Parser extension.

Installation Issues
===================

Composer Install Fails
-----------------------

**Problem**: ``composer require netresearch/nr-xliff-streaming`` fails

**Solutions**:

1. **Check PHP version**:

   .. code-block:: bash

      php -v  # Must be 8.2, 8.3, or 8.4

2. **Check TYPO3 version**:

   Requires TYPO3 13.4 or higher. Update ``composer.json``:

   .. code-block:: json

      "require": {
          "typo3/cms-core": "^13.4"
      }

3. **Clear composer cache**:

   .. code-block:: bash

      composer clear-cache
      composer install

Extension Not Visible in Backend
---------------------------------

**Problem**: Extension doesn't appear in Extension Manager

**Solutions**:

1. **Clear TYPO3 caches**:

   Backend → Maintenance → Flush TYPO3 and PHP Caches

2. **Check extension is loaded**:

   .. code-block:: bash

      ./vendor/bin/typo3 extension:list

3. **Verify composer mode**:

   Extension requires composer mode. Check ``composer.json`` includes:

   .. code-block:: json

      "extra": {
          "typo3/cms": {
              "web-dir": ".Build/web"
          }
      }

Parsing Issues
==============

InvalidXliffException: Missing required "id" attribute
------------------------------------------------------

**Error Code**: 1700000004

**Problem**: XLIFF trans-unit is missing the id attribute

**Solution**:

Ensure all trans-units have an id:

.. code-block:: xml

   <!-- ❌ Wrong -->
   <trans-unit>
       <source>Text</source>
   </trans-unit>

   <!-- ✅ Correct -->
   <trans-unit id="my.unique.id">
       <source>Text</source>
   </trans-unit>

InvalidXliffException: Missing required <source> element
---------------------------------------------------------

**Error Code**: 1700000005

**Problem**: XLIFF trans-unit is missing the source element

**Solution**:

Every trans-unit must have a source element:

.. code-block:: xml

   <!-- ❌ Wrong -->
   <trans-unit id="test">
       <target>Translation</target>
   </trans-unit>

   <!-- ✅ Correct -->
   <trans-unit id="test">
       <source>Original</source>
       <target>Translation</target>
   </trans-unit>

InvalidXliffException: Failed to parse XML content
---------------------------------------------------

**Error Code**: 1700000001

**Problem**: XML is malformed or not well-formed

**Solutions**:

1. **Validate XML syntax**:

   Use an XML validator to check for:

   * Unclosed tags
   * Invalid characters
   * Encoding issues

2. **Check file encoding**:

   XLIFF files should be UTF-8 encoded:

   .. code-block:: xml

      <?xml version="1.0" encoding="UTF-8"?>

3. **Verify XML declaration**:

   First line must be the XML declaration (no whitespace before):

   .. code-block:: xml

      <?xml version="1.0" encoding="UTF-8"?>
      <xliff version="1.2" ...>

External entities are blocked
------------------------------

**Error Code**: 1700000003

**Problem**: XLIFF contains external entity references (security protection)

**This is expected behavior** for security reasons. The parser blocks XXE attacks.

**Solution**:

If you have legitimate entity usage, convert to inline content:

.. code-block:: xml

   <!-- ❌ Blocked (external entity) -->
   <!DOCTYPE xliff [
       <!ENTITY company "Acme Corp">
   ]>
   <trans-unit id="test">
       <source>&company;</source>
   </trans-unit>

   <!-- ✅ Allowed (inline content) -->
   <trans-unit id="test">
       <source>Acme Corp</source>
   </trans-unit>

No trans-units found
--------------------

**Problem**: Parser returns empty results for valid XLIFF

**Solutions**:

1. **Check XLIFF version**:

   Ensure version attribute is present:

   .. code-block:: xml

      <xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">

2. **Verify element names**:

   * XLIFF 1.x uses ``<trans-unit>``
   * XLIFF 2.0 uses ``<unit>`` with ``<segment>``

3. **Check file structure**:

   .. code-block:: xml

      <xliff version="1.2">
          <file>
              <body>
                  <trans-unit id="...">  <!-- Must be inside body -->
                      <source>...</source>
                  </trans-unit>
              </body>
          </file>
      </xliff>

Performance Issues
==================

Parsing is Slower Than Expected
--------------------------------

**Problem**: Processing takes longer than advertised benchmarks

**Solutions**:

1. **Use generator pattern correctly**:

   .. code-block:: php

      // ❌ Wrong (loads all into memory)
      $units = iterator_to_array($parser->parseTransUnits($xliff));

      // ✅ Correct (streams one at a time)
      foreach ($parser->parseTransUnits($xliff) as $unit) {
          processUnit($unit);
      }

2. **Avoid repeated parsing**:

   Parse once, process all units:

   .. code-block:: php

      // ❌ Wrong (parses multiple times)
      for ($i = 0; $i < count($ids); $i++) {
          foreach ($parser->parseTransUnits($xliff) as $unit) {
              if ($unit['id'] === $ids[$i]) {
                  // ...
              }
          }
      }

      // ✅ Correct (parse once)
      $unitsById = [];
      foreach ($parser->parseTransUnits($xliff) as $unit) {
          $unitsById[$unit['id']] = $unit;
      }

3. **Check PHP version**:

   PHP 8.4 is fastest. Upgrade if on PHP 8.2.

High Memory Usage
-----------------

**Problem**: Memory usage exceeds expected ~30MB

**Solutions**:

1. **Don't store all units**:

   .. code-block:: php

      // ❌ Wrong (stores all units)
      $allUnits = iterator_to_array($parser->parseTransUnits($xliff));

      // ✅ Correct (process and release)
      foreach ($parser->parseTransUnits($xliff) as $unit) {
          processAndStore($unit);
          // Unit is released from memory after processing
      }

2. **Process in batches**:

   .. code-block:: php

      $batch = [];
      foreach ($parser->parseTransUnits($xliff) as $unit) {
          $batch[] = $unit;
          if (count($batch) >= 100) {
              processBatch($batch);
              $batch = []; // Release memory
          }
      }

Integration Issues
==================

Parser Not Available via Dependency Injection
----------------------------------------------

**Problem**: Cannot inject ``XliffStreamingParser`` into services

**Solutions**:

1. **Clear TYPO3 caches**:

   .. code-block:: bash

      ./vendor/bin/typo3 cache:flush

2. **Verify Services.yaml exists**:

   File should exist: ``Configuration/Services.yaml``

3. **Check service configuration**:

   .. code-block:: yaml

      services:
        Netresearch\NrXliffStreaming\Parser\XliffStreamingParser:
          public: true

4. **Use fully qualified class name**:

   .. code-block:: php

      use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;

      public function __construct(
          private readonly XliffStreamingParser $xliffParser
      ) {}

Testing Issues
==============

Tests Fail After Installation
------------------------------

**Problem**: ``composer test`` fails

**Solutions**:

1. **Install dev dependencies**:

   .. code-block:: bash

      composer install  # Not composer install --no-dev

2. **Check PHPUnit version**:

   .. code-block:: bash

      .Build/bin/phpunit --version  # Should be 11.x

3. **Run specific test suites**:

   .. code-block:: bash

      composer test:unit        # Unit tests only
      composer test:functional  # Functional tests only

4. **Check PHP extensions**:

   Required: dom, json, libxml, mbstring

   .. code-block:: bash

      php -m | grep -E "(dom|json|libxml|mbstring)"

Getting Help
============

If you can't resolve your issue:

1. **Check GitHub Issues**: https://github.com/netresearch/t3x-nr-xliff-streaming/issues
2. **Search TYPO3 Slack**: #typo3-cms channel
3. **Report a Bug**: Include error message, TYPO3 version, PHP version, and sample XLIFF

Diagnostic Commands
===================

Run these commands to gather diagnostic information:

.. code-block:: bash

   # System information
   php -v
   composer --version
   ./vendor/bin/typo3 --version

   # Extension status
   composer show netresearch/nr-xliff-streaming
   ./vendor/bin/typo3 extension:list | grep nr_xliff

   # Run tests
   composer test:unit

   # Static analysis
   composer analyse

   # Coding standards
   composer fix -- --dry-run

Include this information when reporting issues.
