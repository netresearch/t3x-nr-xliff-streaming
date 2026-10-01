.. SPDX-License-Identifier: CC-BY-4.0
.. SPDX-FileCopyrightText: Netresearch DTT GmbH

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
   - Building the SimpleXML tree adds about eight times the input size to the
     process (measured: 439.6 MB for a 56.6 MB document)
   - Large translation files therefore run into ``memory_limit``

The Solution
============

This extension solves these problems using **XMLReader streaming**:

**One unit at a time:**
   XMLReader streams through the document node by node and builds a tree for
   one translation unit at a time.

**Lower memory:**
   Parsing adds about the input size to the process instead of about eight
   times the input size (measured: 57.5 MB against 439.6 MB for a 56.6 MB
   document). Memory still grows with the file: the document is passed as a
   string, and libxml2 keeps a copy of it while parsing. See
   :ref:`performance`.

Key Features
============

✅ **Memory Efficient**
   About one input size of added memory, against about eight for SimpleXML

✅ **XLIFF Version Support**
   - XLIFF 1.0 (no namespace)
   - XLIFF 1.2 (urn:oasis:names:tc:xliff:document:1.2)
   - XLIFF 2.0 (urn:oasis:names:tc:xliff:document:2.0)

✅ **XXE Protection**
   Built-in security against XML External Entity attacks

✅ **Generator Pattern**
   Memory-efficient iteration using PHP generators

✅ **Dependency Injection**
   TYPO3 13.4 LTS and 14.3 LTS architecture with Services.yaml configuration

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

- TYPO3 13.4 LTS or 14.3 LTS
- PHP 8.2, 8.3, 8.4, or 8.5
- XMLReader PHP extension (standard, typically enabled)
