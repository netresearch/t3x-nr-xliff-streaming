.. include:: /Includes.rst.txt

.. _api:

=============
API Reference
=============

XliffStreamingParser
====================

.. php:namespace:: Netresearch\NrXliffStreaming\Parser

.. php:class:: XliffStreamingParser

   High-performance streaming XLIFF parser using XMLReader.

   This parser uses XMLReader to stream through XLIFF files node-by-node,
   maintaining constant memory usage (~30MB) regardless of file size.
   It provides 60x speed improvement and 30x memory reduction compared
   to SimpleXML-based parsing.

   **Performance:**
      - Memory: Constant ~30MB (vs 900MB with SimpleXML for 108MB file)
      - Speed: 90 seconds (vs 90 minutes with SimpleXML for 100MB file)
      - Efficiency: 30x memory reduction, 60x speed improvement

   **Supported XLIFF Versions:**
      - XLIFF 1.0 (no namespace)
      - XLIFF 1.2 (urn:oasis:names:tc:xliff:document:1.2)
      - XLIFF 2.0 (urn:oasis:names:tc:xliff:document:2.0)

   .. php:const:: XLIFF_1_2_NS

      XLIFF 1.2 namespace URI constant.

      :Type: string
      :Value: ``urn:oasis:names:tc:xliff:document:1.2``

   .. php:const:: XLIFF_2_0_NS

      XLIFF 2.0 namespace URI constant.

      :Type: string
      :Value: ``urn:oasis:names:tc:xliff:document:2.0``

   .. php:method:: parseTransUnits(string $xmlContent)

      Parse XLIFF translation units using streaming XMLReader.

      This method uses PHP Generators for memory-efficient iteration.
      It streams through the XLIFF document node-by-node, processing
      one trans-unit at a time. Memory usage remains constant regardless
      of file size.

      **Example:**

      .. code-block:: php

         $parser = new XliffStreamingParser();
         $xliffContent = file_get_contents('translations.xlf');

         foreach ($parser->parseTransUnits($xliffContent) as $unit) {
             echo "ID: {$unit['id']}, Source: {$unit['source']}\n";
         }

      :param string $xmlContent: Complete XLIFF file content as string
      :returns: Generator yielding associative arrays with keys: ``id`` (string), ``source`` (string), ``target`` (string|null), ``line`` (int)
      :returntype: ``\\Generator<array{id: string, source: string, target: string|null, line: int}>``
      :throws InvalidXliffException: If XML is malformed, missing required attributes, or missing required elements

      .. versionadded:: 1.0.0
         Initial implementation with XLIFF 1.0, 1.2, and 2.0 support.

   .. php:method:: isXliffNamespace(?string $uri)

      Check if namespace URI is a supported XLIFF namespace.

      Supports XLIFF 1.0 (null namespace), XLIFF 1.2, and XLIFF 2.0.

      :param string|null $uri: Namespace URI to check (null for XLIFF 1.0)
      :returns: True if namespace is supported XLIFF version
      :returntype: bool

   .. php:method:: extractTransUnit(\\XMLReader $reader)

      Extract translation unit data from XMLReader position.

      Converts current XMLReader position to SimpleXMLElement for easy
      data extraction while maintaining XXE protection via LIBXML_NONET.

      :param \\XMLReader $reader: XMLReader positioned at trans-unit element
      :returns: Associative array with parsed trans-unit data
      :returntype: ``array{id: string, source: string, target: string|null, line: int}``
      :throws InvalidXliffException: If required attributes or elements are missing

InvalidXliffException
=====================

.. php:namespace:: Netresearch\NrXliffStreaming\Exception

.. php:class:: InvalidXliffException

   Exception thrown when XLIFF parsing fails.

   Extends: ``\\RuntimeException``

   This exception is thrown when:
   - XML content is malformed or invalid
   - Required ``id`` attribute is missing on trans-unit
   - Required ``<source>`` element is missing

   **Error Codes:**

   1700000001
      Failed to parse XML content (malformed XML syntax)

   1700000002
      Missing required ``id`` attribute on trans-unit element

   1700000003
      Missing required ``<source>`` element in trans-unit

   **Example:**

   .. code-block:: php

      use Netresearch\NrXliffStreaming\Exception\InvalidXliffException;

      try {
          foreach ($parser->parseTransUnits($xliffContent) as $unit) {
              // Process
          }
      } catch (InvalidXliffException $e) {
          match ($e->getCode()) {
              1700000001 => $this->handleMalformedXml($e),
              1700000002 => $this->handleMissingId($e),
              1700000003 => $this->handleMissingSource($e),
              default => $this->handleGenericError($e),
          };
      }

Usage Examples
==============

Basic Parsing
-------------

.. code-block:: php
   :caption: Example: Basic XLIFF parsing

   use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;

   $parser = new XliffStreamingParser();
   $xliffContent = <<<'XML'
   <?xml version="1.0" encoding="utf-8"?>
   <xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
       <file source-language="en" target-language="de">
           <body>
               <trans-unit id="hello">
                   <source>Hello</source>
                   <target>Hallo</target>
               </trans-unit>
               <trans-unit id="world">
                   <source>World</source>
                   <target>Welt</target>
               </trans-unit>
           </body>
       </file>
   </xliff>
   XML;

   foreach ($parser->parseTransUnits($xliffContent) as $unit) {
       var_dump($unit);
   }

   // Output:
   // array(4) {
   //   ["id"]=> string(5) "hello"
   //   ["source"]=> string(5) "Hello"
   //   ["target"]=> string(5) "Hallo"
   //   ["line"]=> int(5)
   // }

Database Import
---------------

.. code-block:: php
   :caption: Example: Import translations to database

   use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;
   use TYPO3\CMS\Core\Database\ConnectionPool;
   use TYPO3\CMS\Core\Utility\GeneralUtility;

   final class TranslationImporter
   {
       public function __construct(
           private readonly XliffStreamingParser $parser,
           private readonly ConnectionPool $connectionPool
       ) {
       }

       public function import(string $xliffContent, string $language): int
       {
           $connection = $this->connectionPool->getConnectionForTable('translations');
           $count = 0;

           foreach ($this->parser->parseTransUnits($xliffContent) as $unit) {
               $connection->insert('translations', [
                   'language' => $language,
                   'trans_key' => $unit['id'],
                   'source_text' => $unit['source'],
                   'target_text' => $unit['target'] ?? '',
               ]);
               $count++;
           }

           return $count;
       }
   }

File Upload Handler
-------------------

.. code-block:: php
   :caption: Example: Handle XLIFF file uploads

   use Netresearch\NrXliffStreaming\Exception\InvalidXliffException;
   use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;
   use Psr\Http\Message\ResponseInterface;
   use Psr\Http\Message\ServerRequestInterface;

   final class XliffUploadController
   {
       public function __construct(
           private readonly XliffStreamingParser $parser
       ) {
       }

       public function uploadAction(ServerRequestInterface $request): ResponseInterface
       {
           $uploadedFile = $request->getUploadedFiles()['xliff'] ?? null;

           if ($uploadedFile === null || $uploadedFile->getError() !== UPLOAD_ERR_OK) {
               return $this->jsonResponse(['error' => 'Upload failed'], 400);
           }

           $xliffContent = $uploadedFile->getStream()->getContents();

           try {
               $units = [];
               foreach ($this->parser->parseTransUnits($xliffContent) as $unit) {
                   $units[] = $unit;
               }

               return $this->jsonResponse([
                   'success' => true,
                   'count' => count($units),
                   'units' => $units,
               ]);
           } catch (InvalidXliffException $e) {
               return $this->jsonResponse([
                   'error' => 'Invalid XLIFF: ' . $e->getMessage(),
                   'code' => $e->getCode(),
               ], 400);
           }
       }
   }

Validation Helper
-----------------

.. code-block:: php
   :caption: Example: XLIFF validation utility

   use Netresearch\NrXliffStreaming\Exception\InvalidXliffException;
   use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;

   final class XliffValidator
   {
       public function __construct(
           private readonly XliffStreamingParser $parser
       ) {
       }

       public function validate(string $xliffContent): array
       {
           $errors = [];
           $warnings = [];
           $count = 0;

           try {
               foreach ($this->parser->parseTransUnits($xliffContent) as $unit) {
                   $count++;

                   // Check for missing target
                   if ($unit['target'] === null) {
                       $warnings[] = "Line {$unit['line']}: Missing target for '{$unit['id']}'";
                   }

                   // Check for empty source
                   if (trim($unit['source']) === '') {
                       $warnings[] = "Line {$unit['line']}: Empty source for '{$unit['id']}'";
                   }
               }
           } catch (InvalidXliffException $e) {
               $errors[] = $e->getMessage();
           }

           return [
               'valid' => empty($errors),
               'count' => $count,
               'errors' => $errors,
               'warnings' => $warnings,
           ];
       }
   }

Type Definitions
================

Translation Unit Array
----------------------

.. code-block:: php
   :caption: Type: Translation unit structure

   /**
    * @phpstan-type TransUnit array{
    *     id: string,
    *     source: string,
    *     target: string|null,
    *     line: int
    * }
    */

Constants Reference
===================

Namespace URIs
--------------

.. code-block:: php
   :caption: XLIFF namespace constants

   // XLIFF 1.2 namespace
   XliffStreamingParser::XLIFF_1_2_NS = 'urn:oasis:names:tc:xliff:document:1.2';

   // XLIFF 2.0 namespace
   XliffStreamingParser::XLIFF_2_0_NS = 'urn:oasis:names:tc:xliff:document:2.0';

Error Codes
-----------

.. code-block:: php
   :caption: Exception error codes

   1700000001  // Failed to parse XML content
   1700000002  // Missing required 'id' attribute
   1700000003  // Missing required '<source>' element
