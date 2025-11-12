.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

System Requirements
===================

Before installing the XLIFF Streaming Parser extension, ensure your system meets
the following requirements:

**Required:**
   - TYPO3 13.4 or higher
   - PHP 8.2, 8.3, or 8.4
   - XMLReader PHP extension (standard, typically enabled by default)

**Recommended:**
   - Composer for dependency management
   - DDEV or similar local development environment

Installation via Composer
==========================

The recommended way to install the XLIFF Streaming Parser extension is via Composer.

.. code-block:: bash
   :caption: Terminal

   composer require netresearch/nr-xliff-streaming

This will:

1. Download the extension package
2. Install it in your TYPO3 project
3. Register the extension with TYPO3
4. Configure autoloading via PSR-4

Extension Activation
====================

After installing via Composer, activate the extension:

**Backend Activation:**

1. Log in to the TYPO3 Backend
2. Navigate to **Admin Tools** > **Extensions**
3. Search for "xliff streaming" or "nr_xliff_streaming"
4. Click the **Activate** button

**CLI Activation:**

.. code-block:: bash
   :caption: Terminal

   vendor/bin/typo3 extension:activate nr_xliff_streaming

Verification
============

To verify the extension is installed and working correctly:

**Check Extension Status:**

.. code-block:: bash
   :caption: Terminal

   vendor/bin/typo3 extension:list | grep nr_xliff_streaming

Expected output:

.. code-block:: text

   nr_xliff_streaming    1.0.0    active    XLIFF Streaming Parser

**Test Parser Service:**

Create a simple test in your TYPO3 project:

.. code-block:: php
   :caption: Test Script (e.g., typo3conf/ext/myext/Tests/Manual/TestParser.php)

   use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;
   use TYPO3\CMS\Core\Utility\GeneralUtility;

   $parser = GeneralUtility::makeInstance(XliffStreamingParser::class);
   $xliffContent = file_get_contents('path/to/test.xlf');

   foreach ($parser->parseTransUnits($xliffContent) as $unit) {
       echo "ID: {$unit['id']}, Source: {$unit['source']}\n";
   }

Development Environment
=======================

For local development, the extension includes DDEV configuration.

.. code-block:: bash
   :caption: Terminal

   # Clone repository
   git clone https://github.com/netresearch/t3x-nr-xliff-streaming.git
   cd t3x-nr-xliff-streaming

   # Start DDEV
   ddev start

   # Install dependencies
   ddev composer install

   # Run tests
   ddev composer test:unit

Troubleshooting
===============

**Extension Not Found:**

If the extension is not found after installation:

1. Clear all caches: ``vendor/bin/typo3 cache:flush``
2. Verify composer.json includes the package
3. Check TYPO3 version compatibility (13.4+)

**XMLReader Extension Missing:**

If you encounter errors about XMLReader not being available:

.. code-block:: bash
   :caption: Check PHP extensions

   php -m | grep xmlreader

If missing, install via package manager:

.. code-block:: bash
   :caption: Ubuntu/Debian

   sudo apt-get install php-xml

.. code-block:: bash
   :caption: macOS (Homebrew)

   brew install php@8.2
   # XMLReader included by default

**Memory Limit Issues:**

If you encounter memory limit errors even with streaming:

.. code-block:: ini
   :caption: php.ini

   memory_limit = 256M

The streaming parser should maintain constant ~30MB memory usage regardless of
file size, but ensure your PHP configuration allows at least 128MB.

Next Steps
==========

Now that the extension is installed, proceed to:

- :ref:`integration` - Learn how to use the parser in your extension
- :ref:`api` - Review the complete API reference
- :ref:`performance` - Understand performance characteristics
