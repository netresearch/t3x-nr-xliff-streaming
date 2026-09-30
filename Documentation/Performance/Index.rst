.. SPDX-License-Identifier: CC-BY-4.0
.. SPDX-FileCopyrightText: Netresearch DTT GmbH

.. include:: /Includes.rst.txt

.. _performance:

===========
Performance
===========

Overview
========

The parser reads the document with XMLReader and builds a tree for one
translation unit at a time. SimpleXML builds a tree of the whole document.
Streaming therefore needs far less memory. It is not faster, and its memory use
is not independent of the file size, because ``parseTransUnits()`` takes the
whole document as a string.

Measured Results
================

Measured on PHP 8.5.10 with libxml2 2.9.14 on generated XLIFF 1.2 documents
(one ``<trans-unit>`` with ``<source>`` and ``<target>`` per line). The
streaming column iterates ``parseTransUnits()``; the SimpleXML column runs
``simplexml_load_string()`` and one ``//trans-unit`` XPath query and reads id,
source and target of every unit. "Added memory" is the growth of the process's
resident memory (``VmHWM`` after resetting it, minus ``VmRSS`` before) while
parsing, on top of the input string the caller already holds.

.. list-table:: Streaming parser and SimpleXML
   :header-rows: 1

   * - Input size
     - Units
     - Streaming added memory
     - SimpleXML added memory
     - Streaming time
     - SimpleXML time
   * - 1.7 MB
     - 10,000
     - 2.4 MB
     - 13.4 MB
     - 0.11 s
     - 0.01 s
   * - 16.9 MB
     - 100,000
     - 17.4 MB
     - 131.6 MB
     - 1.14 s
     - 0.14 s
   * - 56.6 MB
     - 330,000
     - 57.5 MB
     - 439.6 MB
     - 3.96 s
     - 0.42 s

What the numbers mean
---------------------

- **Memory:** parsing adds about the input size, because libxml2 keeps its own
  copy of the input buffer. Together with the caller's string, peak memory is
  about twice the input size plus a constant. SimpleXML adds about eight times
  the input size.
- **PHP's view:** ``memory_get_peak_usage()`` sees only the input string plus
  less than 1 MB. libxml2 allocates outside PHP's memory manager, so
  ``memory_limit`` does not count its copy.
- **Speed:** a single SimpleXML XPath query was 8 to 11 times faster. The
  streaming parser parses each unit a second time on its own
  (``readOuterXml()`` and ``simplexml_load_string()``).
- **Scaling:** time and memory grew linearly with the input size.

Earlier versions of this page reported a constant memory footprint of about
30 MB, a 30x memory reduction and a 60x speed-up (90 minutes against 90
seconds for a 100 MB file). Nothing in this repository reproduces those
figures, and the measurement above contradicts them, so they were removed.

Why streaming needs less memory
===============================

**SimpleXML approach:**

1. Parse the entire document into a tree
2. Query the tree with XPath
3. Iterate through the results

**Streaming approach:**

1. Move a cursor through the document node by node
2. Build a tree only for the current ``<trans-unit>`` or ``<unit>``
3. Yield the unit and discard its tree

.. _performance-optimization:

Optimization Tips
=================

File Handling
-------------

**DO:**

.. code-block:: php
   :caption: Efficient: Read file once, stream through content

   $xliffContent = file_get_contents('large-file.xlf');
   foreach ($parser->parseTransUnits($xliffContent) as $unit) {
       $this->processUnit($unit);
   }

**DON'T:**

.. code-block:: php
   :caption: Inefficient: Re-reading file repeatedly

   // ❌ Bad: Multiple file reads
   foreach ($parser->parseTransUnits(file_get_contents('file.xlf')) as $unit) {
       // This re-reads the file on each iteration!
   }

Batch Processing
----------------

**DO:**

.. code-block:: php
   :caption: Efficient: Process immediately

   foreach ($parser->parseTransUnits($xliffContent) as $unit) {
       $this->database->insert('translations', $unit);
   }

**DON'T:**

.. code-block:: php
   :caption: Inefficient: Buffering all units

   // ❌ Bad: Negates streaming benefits
   $units = iterator_to_array($parser->parseTransUnits($xliffContent));
   foreach ($units as $unit) {
       $this->database->insert('translations', $unit);
   }

Generator Usage
---------------

The parser returns a Generator for memory efficiency:

.. code-block:: php
   :caption: Understanding Generators

   // ✅ Good: Streaming iteration
   foreach ($parser->parseTransUnits($xliffContent) as $unit) {
       // Each unit processed individually
       // Previous units garbage collected
   }

   // ❌ Bad: Converting to array
   $units = iterator_to_array($parser->parseTransUnits($xliffContent));
   // Now all units in memory at once!

Database Operations
-------------------

For database imports, use batch inserts:

.. code-block:: php
   :caption: Optimized database operations

   $batch = [];
   $batchSize = 100;

   foreach ($parser->parseTransUnits($xliffContent) as $unit) {
       $batch[] = $unit;

       if (count($batch) >= $batchSize) {
           $connection->bulkInsert('translations', $batch);
           $batch = [];
       }
   }

   // Insert remaining units
   if (!empty($batch)) {
       $connection->bulkInsert('translations', $batch);
   }

Benchmarking
============

Running Your Own Benchmarks
----------------------------

To benchmark the parser with your own files:

.. code-block:: php
   :caption: Simple benchmark script

   use Netresearch\NrXliffStreaming\Parser\XliffStreamingParser;

   $parser = new XliffStreamingParser();
   $xliffContent = file_get_contents('your-file.xlf');

   // Measure memory before
   $memoryBefore = memory_get_usage(true);
   $timeBefore = microtime(true);

   $count = 0;
   foreach ($parser->parseTransUnits($xliffContent) as $unit) {
       $count++;
   }

   // Measure after
   $timeAfter = microtime(true);
   $memoryAfter = memory_get_usage(true);
   $memoryPeak = memory_get_peak_usage(true);

   printf("Processed: %d units\n", $count);
   printf("Time: %.2f seconds\n", $timeAfter - $timeBefore);
   printf("Memory used: %.2f MB\n", ($memoryAfter - $memoryBefore) / 1024 / 1024);
   printf("Peak memory: %.2f MB\n", $memoryPeak / 1024 / 1024);

Performance Monitoring
----------------------

For production monitoring:

.. code-block:: php
   :caption: Production monitoring example

   use Psr\Log\LoggerInterface;

   final class MonitoredXliffImporter
   {
       public function __construct(
           private readonly XliffStreamingParser $parser,
           private readonly LoggerInterface $logger
       ) {
       }

       public function import(string $xliffContent): array
       {
           $start = microtime(true);
           $memoryStart = memory_get_usage(true);
           $count = 0;

           foreach ($this->parser->parseTransUnits($xliffContent) as $unit) {
               $this->processUnit($unit);
               $count++;
           }

           $duration = microtime(true) - $start;
           $memoryUsed = memory_get_usage(true) - $memoryStart;

           $this->logger->info('XLIFF import completed', [
               'units' => $count,
               'duration_seconds' => round($duration, 2),
               'memory_mb' => round($memoryUsed / 1024 / 1024, 2),
               'throughput_units_per_second' => round($count / $duration),
           ]);

           return [
               'units' => $count,
               'duration' => $duration,
               'memory' => $memoryUsed,
           ];
       }
   }

Frequently Asked Questions
===========================

**Q: Will streaming help with small files (<1MB)?**

A: Not in speed: SimpleXML was faster at every size measured above. The
   memory saving is small in absolute terms for small files.

**Q: Can I process files larger than PHP's memory limit?**

A: No. The document is passed as a string, and that string counts against
   ``memory_limit``. libxml2's copy of it does not count against
   ``memory_limit`` but does occupy process memory.

**Q: Does streaming work with compressed XLIFF files?**

A: Decompress first, then parse:

   .. code-block:: php

      $xliffContent = gzdecode(file_get_contents('file.xlf.gz'));
      foreach ($parser->parseTransUnits($xliffContent) as $unit) {
          // ...
      }

**Q: How does performance compare to other XLIFF libraries?**

A: No comparison with other libraries has been measured. Parsers that
   build a DOM or SimpleXML tree of the whole document need memory of the
   order measured for SimpleXML above.

Next Steps
==========

- :ref:`security` - XXE protection and security best practices
- :ref:`integration` - Learn how to integrate into your extension
