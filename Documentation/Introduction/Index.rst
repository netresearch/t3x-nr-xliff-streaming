.. include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============

What is XLIFF Streaming Parser?
================================

The XLIFF Streaming Parser is a high-performance TYPO3 extension that provides
memory-efficient parsing of large XLIFF (XML Localization Interchange File Format)
translation files using XMLReader streaming technology.

The Problem
===========

Traditional XLIFF parsing in TYPO3 uses PHP's SimpleXML, which loads the entire
XML document into memory. This approach causes severe problems with large translation
files:

**Memory Issues:**
   - 10MB XLIFF file → 80-90MB memory usage
   - 100MB XLIFF file → 800-900MB memory usage
   - Memory consumption scales 8-9x with file size

**Performance Issues:**
   - Large files (>10MB) cause 5-10 minute upload timeouts
   - Processing times become exponentially longer
   - Server resources exhausted on concurrent uploads

**Real-World Impact:**
   - Translators cannot import large translation memories
   - Batch translation imports fail
   - Manual splitting of files required (time-consuming, error-prone)

The Solution
============

This extension solves these problems using **XMLReader streaming**:

**Constant Memory:**
   XMLReader streams through the file node-by-node, processing one translation
   unit at a time. Memory usage stays constant (~30MB) regardless of file size.

**60x Performance:**
   Benchmarks show 60x speed improvement for large files:

   - SimpleXML: 90 minutes for 100MB file
   - XMLReader Streaming: 90 seconds for same file

**30x Memory Efficiency:**
   Real-world measurements show 30x memory reduction:

   - SimpleXML: 900MB for 108MB file
   - XMLReader Streaming: 30MB for same file

Key Features
============

✅ **High Performance**
   60x faster than SimpleXML for large files

✅ **Memory Efficient**
   30x memory reduction with constant memory footprint

✅ **XLIFF Version Support**
   - XLIFF 1.0 (no namespace)
   - XLIFF 1.2 (urn:oasis:names:tc:xliff:document:1.2)
   - XLIFF 2.0 (urn:oasis:names:tc:xliff:document:2.0)

✅ **XXE Protection**
   Built-in security against XML External Entity attacks

✅ **Generator Pattern**
   Memory-efficient iteration using PHP generators

✅ **Dependency Injection**
   Modern TYPO3 13.x architecture with Services.yaml configuration

✅ **Drop-in Replacement**
   Easy migration from SimpleXML to streaming parser

When to Use This Extension
===========================

**Use this extension when:**
   - Processing translation files larger than 1MB
   - Handling batch translation imports
   - Working with translation memory exports
   - Experiencing memory limit errors with XLIFF imports
   - Need predictable memory usage for large files

**Not needed for:**
   - Small translation files (<1MB)
   - One-time small imports
   - Static translation files bundled with extensions

.. versionadded:: 1.0.0
   Initial release supporting XLIFF 1.0, 1.2, and 2.0 with XXE protection.

Requirements
============

- TYPO3 13.4 or higher
- PHP 8.2, 8.3, or 8.4
- XMLReader PHP extension (standard, typically enabled)
