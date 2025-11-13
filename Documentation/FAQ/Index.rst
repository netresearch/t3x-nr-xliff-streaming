.. include:: /Includes.rst.txt

.. _faq:

==========================
Frequently Asked Questions
==========================

General Questions
=================

What is the XLIFF Streaming Parser?
------------------------------------

The XLIFF Streaming Parser is a high-performance TYPO3 extension that uses XMLReader
to parse XLIFF translation files with constant memory usage, making it ideal for
processing large translation files (10MB+).

Which XLIFF versions are supported?
------------------------------------

The parser supports:

* **XLIFF 1.0** - No namespace
* **XLIFF 1.2** - ``urn:oasis:names:tc:xliff:document:1.2``
* **XLIFF 2.0** - ``urn:oasis:names:tc:xliff:document:2.0``

All versions are automatically detected and handled correctly.

Performance Questions
=====================

How much faster is it compared to SimpleXML?
---------------------------------------------

For large files (100MB+), the streaming parser is approximately:

* **60x faster** in processing time
* **30x more memory efficient**

For example, a 108MB file that takes 90 minutes with SimpleXML can be parsed in
90 seconds with the streaming parser.

See :ref:`performance` for detailed benchmarks.

What is the maximum file size it can handle?
---------------------------------------------

There is no practical file size limit. The parser uses constant memory (typically
30MB) regardless of file size. Files over 1GB have been successfully tested.

Does performance degrade with file size?
-----------------------------------------

Processing time scales linearly with file size, but memory usage remains constant.
A 1GB file uses the same ~30MB of memory as a 1MB file.

Security Questions
==================

Is the parser protected against XXE attacks?
--------------------------------------------

Yes, comprehensive protection is built-in:

* ``LIBXML_NONET`` flag prevents external entity resolution
* Entity expansion loops are detected and blocked
* All XXE attack vectors are tested and mitigated

See :ref:`security` for details.

What security standards does it comply with?
---------------------------------------------

The extension complies with:

* **CWE-611**: XML External Entities (XXE)
* **CWE-776**: Improper Restriction of Recursive Entity References
* **OWASP Top 10**: XML External Entities
* **TYPO3 Security Guidelines**

Integration Questions
=====================

Can I use it with dependency injection?
----------------------------------------

Yes, that's the recommended approach:

.. code-block:: php

   public function __construct(
       private readonly XliffStreamingParser $xliffParser
   ) {}

The parser is registered as a public service in ``Configuration/Services.yaml``.

Can I use it outside of TYPO3?
-------------------------------

Yes, the parser is standalone and only requires PHP 8.2+. However, it's optimized
for TYPO3 integration and uses TYPO3 testing framework for tests.

Does it work with custom XLIFF extensions?
-------------------------------------------

The parser focuses on standard XLIFF elements (trans-unit/unit, source, target).
Custom elements or attributes are ignored but don't cause errors.

How do I migrate from SimpleXML?
---------------------------------

See :ref:`integration-migration` for a step-by-step migration guide with code examples.

Troubleshooting
===============

What if my XLIFF file doesn't parse?
-------------------------------------

Common issues:

1. **Malformed XML**: Validate your XLIFF with an XML validator
2. **Missing required elements**: Ensure all trans-units have id and source
3. **External entities**: The parser blocks external entities for security

See :ref:`troubleshooting` for detailed solutions.

Can I get more detailed error messages?
----------------------------------------

Yes, all exceptions include:

* Error code (e.g., 1700000004)
* Descriptive message
* Line number where the error occurred

Why does parsing fail with security warnings?
----------------------------------------------

If you see warnings about external entities, this is expected security behavior.
The parser blocks XXE attacks by design. If your XLIFF contains entity references,
they must be internal only.

Performance Issues
==================

Parser seems slower than expected?
-----------------------------------

Check these common issues:

1. **Not using generator pattern**: Use ``foreach`` to iterate, not ``iterator_to_array()``
2. **Multiple parses**: Reuse the parser instance for multiple files
3. **Large string concatenation**: Process units individually, don't concatenate all results

See :ref:`performance-optimization` for tips.

Memory usage is higher than expected?
--------------------------------------

The constant memory footprint is ~30MB. If you see higher usage:

1. Ensure you're using generator iteration (``foreach``)
2. Don't store all units in memory at once
3. Process units as they're yielded

Development
===========

How do I run the tests?
------------------------

.. code-block:: bash

   composer install
   composer test          # Run all tests
   composer test:unit     # Unit tests only
   composer test:functional  # Functional tests only

How do I contribute?
--------------------

1. Fork the repository
2. Create a feature branch
3. Follow TYPO3 Coding Standards
4. Ensure all tests pass (``composer test``)
5. Submit a pull request

Where can I report bugs?
-------------------------

Report issues at: https://github.com/netresearch/t3x-nr-xliff-streaming/issues

Include:

* TYPO3 version
* PHP version
* Error message and stack trace
* Sample XLIFF file (if possible)
