.. include:: /Includes.rst.txt

.. _integration:

===========
Integration
===========

Basic Usage
===========

The XLIFF Streaming Parser is designed as a drop-in replacement for TYPO3's
SimpleXML-based XLIFF parsing. It uses dependency injection for easy integration
into your extension.

Dependency Injection
====================

The recommended way to use the parser is via dependency injection:

.. code-block:: php
   :caption: Classes/Controller/TranslationController.php

   namespace Vendor\MyExtension\Controller;

   use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;
   use Psr\Http\Message\ResponseInterface;
   use Psr\Http\Message\ServerRequestInterface;

   final class TranslationController
   {
       public function __construct(
           private readonly XliffStreamingParser $xliffParser
       ) {
       }

       public function importAction(ServerRequestInterface $request): ResponseInterface
       {
           $uploadedFile = $request->getUploadedFiles()['xliff'];
           $xliffContent = $uploadedFile->getStream()->getContents();

           foreach ($this->xliffParser->parseTransUnits($xliffContent) as $unit) {
               // Process each translation unit
               $this->processTransUnit($unit);
           }

           return $this->jsonResponse(['success' => true]);
       }

       private function processTransUnit(array $unit): void
       {
           // Access parsed data
           $id = $unit['id'];           // Translation unit ID
           $source = $unit['source'];   // Source text
           $target = $unit['target'];   // Target text (or null)
           $line = $unit['line'];       // Line number in XML

           // Your processing logic here
       }
   }

Manual Instantiation
====================

If dependency injection is not available, instantiate manually:

.. code-block:: php
   :caption: Alternative: Manual instantiation

   use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;
   use TYPO3\CMS\Core\Utility\GeneralUtility;

   $parser = GeneralUtility::makeInstance(XliffStreamingParser::class);

Parsing XLIFF Files
====================

The parser accepts XLIFF content as a string and returns a PHP Generator:

.. code-block:: php
   :caption: Example: Parse XLIFF file

   $xliffContent = file_get_contents('path/to/translations.xlf');

   foreach ($parser->parseTransUnits($xliffContent) as $unit) {
       printf(
           "ID: %s\nSource: %s\nTarget: %s\nLine: %d\n\n",
           $unit['id'],
           $unit['source'],
           $unit['target'] ?? 'N/A',
           $unit['line']
       );
   }

Return Value Structure
======================

Each translation unit is returned as an associative array:

.. code-block:: php
   :caption: Translation unit array structure

   [
       'id' => 'translation.key',          // string (required)
       'source' => 'Source text',          // string (required)
       'target' => 'Target text',          // string|null (optional)
       'line' => 42,                       // int (XML line number)
   ]

**Field Details:**

id
   Translation unit identifier from the ``id`` attribute

source
   Source language text from the ``<source>`` element

target
   Target language text from the ``<target>`` element, or ``null`` if missing

line
   Line number in the XML file where this trans-unit appears

XLIFF Version Support
======================

The parser automatically detects and handles multiple XLIFF versions:

XLIFF 1.0 (No Namespace)
-------------------------

.. code-block:: xml
   :caption: XLIFF 1.0 example

   <?xml version="1.0" encoding="utf-8" standalone="yes" ?>
   <xliff version="1.0">
       <file source-language="en" target-language="de" datatype="plaintext">
           <body>
               <trans-unit id="key1">
                   <source>Hello</source>
                   <target>Hallo</target>
               </trans-unit>
           </body>
       </file>
   </xliff>

XLIFF 1.2 (With Namespace)
---------------------------

.. code-block:: xml
   :caption: XLIFF 1.2 example

   <?xml version="1.0" encoding="utf-8" standalone="yes" ?>
   <xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
       <file source-language="en" target-language="de" datatype="plaintext">
           <body>
               <trans-unit id="key1">
                   <source>Hello</source>
                   <target>Hallo</target>
               </trans-unit>
           </body>
       </file>
   </xliff>

XLIFF 2.0
---------

.. code-block:: xml
   :caption: XLIFF 2.0 example

   <?xml version="1.0" encoding="UTF-8"?>
   <xliff version="2.0" xmlns="urn:oasis:names:tc:xliff:document:2.0"
          srcLang="en" trgLang="de">
       <file id="file1">
           <unit id="key1">
               <segment>
                   <source>Hello</source>
                   <target>Hallo</target>
               </segment>
           </unit>
       </file>
   </xliff>

Error Handling
==============

The parser throws ``InvalidXliffException`` for malformed XLIFF:

.. code-block:: php
   :caption: Example: Error handling

   use Netresearch\NrXliffStreaming\Exception\InvalidXliffException;

   try {
       foreach ($parser->parseTransUnits($xliffContent) as $unit) {
           // Process unit
       }
   } catch (InvalidXliffException $e) {
       // Handle parsing error
       $this->logger->error('XLIFF parsing failed', [
           'message' => $e->getMessage(),
           'code' => $e->getCode(),
       ]);
   }

**Error Codes:**

1700000001
   Failed to parse XML content (malformed XML syntax)

1700000002
   Missing required ``id`` attribute on trans-unit

1700000003
   Missing required ``<source>`` element

Migration from SimpleXML
=========================

Migrating from TYPO3's SimpleXML parsing to streaming:

**Before (SimpleXML):**

.. code-block:: php
   :caption: Old approach with SimpleXML

   $xml = simplexml_load_string($xliffContent);
   $transUnits = $xml->xpath('//trans-unit');

   foreach ($transUnits as $unit) {
       $id = (string) $unit['id'];
       $source = (string) $unit->source;
       $target = (string) $unit->target;
       // Process...
   }

**After (Streaming):**

.. code-block:: php
   :caption: New approach with streaming parser

   foreach ($parser->parseTransUnits($xliffContent) as $unit) {
       $id = $unit['id'];
       $source = $unit['source'];
       $target = $unit['target'] ?? '';
       // Process...
   }

**Benefits:**

- 30x memory reduction (constant ~30MB vs 900MB for large files)
- 60x speed improvement (90 seconds vs 90 minutes)
- Same functional output (compatible data structure)
- Built-in XXE protection

Memory-Efficient Processing
============================

The parser uses PHP Generators for constant memory usage:

.. code-block:: php
   :caption: Example: Processing large files efficiently

   // Memory usage remains ~30MB regardless of file size
   $xliffContent = file_get_contents('large-100MB-file.xlf');

   $count = 0;
   foreach ($parser->parseTransUnits($xliffContent) as $unit) {
       // Each iteration processes one trans-unit
       // Previous units are garbage collected
       $this->database->insert('translations', $unit);
       $count++;
   }

   echo "Processed {$count} translation units\n";

**Key Points:**

- File size irrelevant to memory usage
- One trans-unit in memory at a time
- Previous units automatically garbage collected
- Constant ~30MB memory footprint

Batch Processing
================

For batch imports with multiple files:

.. code-block:: php
   :caption: Example: Batch file processing

   $files = ['de.xlf', 'fr.xlf', 'es.xlf', 'it.xlf'];

   foreach ($files as $file) {
       $xliffContent = file_get_contents($file);

       foreach ($parser->parseTransUnits($xliffContent) as $unit) {
           $this->importTranslation($file, $unit);
       }
   }

Testing Integration
===================

When testing your integration:

.. code-block:: php
   :caption: Tests/Unit/Controller/TranslationControllerTest.php

   namespace Vendor\MyExtension\Tests\Unit\Controller;

   use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;
   use PHPUnit\Framework\Attributes\Test;
   use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
   use Vendor\MyExtension\Controller\TranslationController;

   final class TranslationControllerTest extends UnitTestCase
   {
       #[Test]
       public function importsXliffTranslations(): void
       {
           $parser = new XliffStreamingParser();
           $controller = new TranslationController($parser);

           // Test your controller logic
       }
   }

Next Steps
==========

- :ref:`api` - Complete PHP API reference
- :ref:`performance` - Performance benchmarks and optimization tips
- :ref:`security` - XXE protection and security best practices
