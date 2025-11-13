.. include:: /Includes.rst.txt

.. _changelog:

=========
Changelog
=========

All notable changes to this project will be documented in this file.

The format is based on `Keep a Changelog <https://keepachangelog.com/en/1.0.0/>`__,
and this project adheres to `Semantic Versioning <https://semver.org/spec/v2.0.0.html>`__.

----

Version 1.0.0 - TBD
===================

Initial Release
---------------

**Added**

* High-performance streaming XLIFF parser using XMLReader
* Support for XLIFF 1.0, 1.2, and 2.0 formats
* Generator pattern for constant memory footprint
* Comprehensive XXE attack protection (CWE-611)
* Billion Laughs DoS attack mitigation
* Full dependency injection support
* TYPO3 13.4 compatibility
* PHP 8.2, 8.3, and 8.4 support
* Complete RST documentation with performance and security sections
* 17 unit tests with security test coverage
* PHPStan level 9 static analysis
* TYPO3 Coding Standards compliance

**Performance**

* 30x memory reduction compared to SimpleXML
* 60x speed improvement for large files
* Constant memory usage independent of file size
* Tested with files up to 108MB

**Security**

* LIBXML_NONET flag prevents external entity attacks
* Entity expansion loop detection
* Fail-safe error handling with descriptive exceptions
* Comprehensive security test suite

----

Version 0.9.0-dev - 2025-01-13
==============================

Beta Release
------------

**Added**

* Complete test infrastructure with fixtures
* Functional tests for TYPO3 integration
* Performance benchmark tests
* GitHub Actions CI/CD workflow
* XliffParserInterface for better architecture
* Extended edge case test coverage
* Changelog documentation
* FAQ documentation
* Troubleshooting guide

**Changed**

* Improved test organization with fixture files
* Enhanced CI/CD automation
* Updated documentation structure

**Fixed**

* PHP 8.4 compatibility for namespace handling
* XXE security test expectations
* Billion Laughs attack detection
