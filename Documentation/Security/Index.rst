.. include:: /Includes.rst.txt

.. _security:

========
Security
========

XXE Protection
==============

The XLIFF Streaming Parser includes comprehensive protection against XML External
Entity (XXE) attacks, a critical security vulnerability affecting XML parsers.

What is XXE?
------------

XML External Entity (XXE) attacks exploit vulnerable XML parsers that process
external entity references in XML documents. Attackers can:

- Read arbitrary files from the server (``file:///etc/passwd``)
- Perform Server-Side Request Forgery (SSRF) attacks
- Cause Denial of Service (DoS) via billion laughs attack
- Execute remote code in certain configurations

**Vulnerability:** CWE-611 - Improper Restriction of XML External Entity Reference

XXE Attack Examples
-------------------

**File Read Attack:**

.. code-block:: xml
   :caption: Malicious XLIFF attempting to read /etc/passwd

   <?xml version="1.0" encoding="UTF-8"?>
   <!DOCTYPE xliff [
       <!ENTITY xxe SYSTEM "file:///etc/passwd">
   ]>
   <xliff version="1.2">
       <file>
           <body>
               <trans-unit id="attack">
                   <source>&xxe;</source>
               </trans-unit>
           </body>
       </file>
   </xliff>

**Network SSRF Attack:**

.. code-block:: xml
   :caption: Malicious XLIFF attempting internal network access

   <?xml version="1.0" encoding="UTF-8"?>
   <!DOCTYPE xliff [
       <!ENTITY xxe SYSTEM "http://internal-server/admin">
   ]>
   <xliff version="1.2">
       <file>
           <body>
               <trans-unit id="attack">
                   <source>&xxe;</source>
               </trans-unit>
           </body>
       </file>
   </xliff>

**Billion Laughs DoS:**

.. code-block:: xml
   :caption: Malicious XLIFF attempting DoS via entity expansion

   <?xml version="1.0" encoding="UTF-8"?>
   <!DOCTYPE xliff [
       <!ENTITY lol "lol">
       <!ENTITY lol2 "&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;">
       <!ENTITY lol3 "&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;">
   ]>
   <xliff version="1.2">
       <file>
           <body>
               <trans-unit id="attack">
                   <source>&lol3;</source>
               </trans-unit>
           </body>
       </file>
   </xliff>

Built-in Protection
===================

The parser provides two layers of XXE protection:

Layer 1: XMLReader Validation
------------------------------

XMLReader itself does not automatically load external entities by default,
providing the first layer of defense.

.. code-block:: php
   :caption: Classes/Parser/XliffStreamingParser.php (excerpt)

   $reader = new \XMLReader();
   $reader->XML($xmlContent, 'UTF-8', LIBXML_NONET);
   // LIBXML_NONET disables network access during parsing

Layer 2: SimpleXMLElement Protection
-------------------------------------

When converting XMLReader nodes to SimpleXMLElement for data extraction,
``LIBXML_NONET`` flag is explicitly enforced:

.. code-block:: php
   :caption: Classes/Parser/XliffStreamingParser.php (excerpt)

   private function extractTransUnit(\XMLReader $reader): array
   {
       $xml = $reader->readOuterXml();

       // LIBXML_NONET prevents external entity loading
       $element = simplexml_load_string(
           $xml,
           \SimpleXMLElement::class,
           LIBXML_NONET
       );

       // Extract data from $element
   }

**LIBXML_NONET:** Disable network access during XML loading. This prevents:

- Loading external DTDs from network
- Loading external entities via HTTP/HTTPS
- Loading external entities via FTP
- Any network-based entity resolution

Security Testing
================

The extension includes comprehensive XXE protection tests:

Unit Tests
----------

.. code-block:: php
   :caption: Tests/Unit/Parser/XliffStreamingParserXXETest.php

   /**
    * XXE Protection Test Suite
    *
    * Validates protection against:
    * - File read attacks (file:// protocol)
    * - Network SSRF attacks (http:// protocol)
    * - Billion laughs DoS attacks (entity expansion)
    * - PHP wrapper attacks (php:// protocol)
    */
   final class XliffStreamingParserXXETest extends UnitTestCase
   {
       #[Test]
       public function xxePayloadWithFileReadIsBlocked(): void
       {
           // Verifies file:///etc/passwd payloads are blocked
       }

       #[Test]
       public function xxePayloadWithNetworkAccessIsBlocked(): void
       {
           // Verifies http:// SSRF payloads are blocked
       }

       #[Test]
       public function billionLaughsAttackIsMitigated(): void
       {
           // Verifies entity expansion bombs are blocked
       }

       #[Test]
       public function xxePayloadWithPhpWrapperIsBlocked(): void
       {
           // Verifies php:// wrapper payloads are blocked
       }

       #[Test]
       public function ssrfAttackViaXxeIsBlocked(): void
       {
           // Verifies internal network access is blocked
       }
   }

Running Security Tests
----------------------

.. code-block:: bash
   :caption: Execute security test suite

   # Run all unit tests (includes XXE tests)
   composer test:unit

   # Run only XXE security tests
   vendor/bin/phpunit Tests/Unit/Parser/XliffStreamingParserXXETest.php

   # Expected output: All tests passing

Security Best Practices
========================

Input Validation
----------------

Always validate XLIFF uploads:

.. code-block:: php
   :caption: Recommended upload validation

   public function uploadAction(ServerRequestInterface $request): ResponseInterface
   {
       $uploadedFile = $request->getUploadedFiles()['xliff'] ?? null;

       // Validate file upload
       if ($uploadedFile === null || $uploadedFile->getError() !== UPLOAD_ERR_OK) {
           return $this->jsonResponse(['error' => 'Upload failed'], 400);
       }

       // Validate file size (prevent DoS)
       $maxSize = 100 * 1024 * 1024; // 100MB
       if ($uploadedFile->getSize() > $maxSize) {
           return $this->jsonResponse(['error' => 'File too large'], 413);
       }

       // Validate MIME type
       $allowedTypes = ['application/xml', 'text/xml', 'application/x-xliff+xml'];
       if (!in_array($uploadedFile->getClientMediaType(), $allowedTypes, true)) {
           return $this->jsonResponse(['error' => 'Invalid file type'], 415);
       }

       // Parse with built-in XXE protection
       try {
           $xliffContent = $uploadedFile->getStream()->getContents();
           foreach ($this->parser->parseTransUnits($xliffContent) as $unit) {
               $this->processUnit($unit);
           }
       } catch (InvalidXliffException $e) {
           return $this->jsonResponse(['error' => 'Invalid XLIFF'], 400);
       }

       return $this->jsonResponse(['success' => true]);
   }

Error Handling
--------------

Don't expose internal details in error messages:

.. code-block:: php
   :caption: Secure error handling

   try {
       foreach ($parser->parseTransUnits($xliffContent) as $unit) {
           // Process
       }
   } catch (InvalidXliffException $e) {
       // ❌ Bad: Exposes internal details
       // return ['error' => $e->getMessage()];

       // ✅ Good: Generic error message
       $this->logger->error('XLIFF parsing failed', [
           'exception' => $e,
           'user' => $backendUser->uid,
       ]);

       return ['error' => 'Invalid XLIFF file format'];
   }

Access Control
--------------

Restrict XLIFF upload functionality to authorized users:

.. code-block:: php
   :caption: Access control example

   use TYPO3\CMS\Core\Context\Context;
   use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

   public function uploadAction(ServerRequestInterface $request): ResponseInterface
   {
       // Verify backend user authentication
       $backendUser = $GLOBALS['BE_USER'] ?? null;
       if ($backendUser === null || !$backendUser->isAdmin()) {
           $this->addFlashMessage(
               'You must be an administrator to upload translation files.',
               'Access Denied',
               ContextualFeedbackSeverity::ERROR
           );
           return $this->redirect('list');
       }

       // Proceed with upload
   }

File System Security
--------------------

Store uploaded XLIFF files securely:

.. code-block:: php
   :caption: Secure file storage

   use TYPO3\CMS\Core\Core\Environment;
   use TYPO3\CMS\Core\Utility\GeneralUtility;

   public function storeUpload(UploadedFileInterface $file): string
   {
       // Store in TYPO3 var directory (not web-accessible)
       $uploadDir = Environment::getVarPath() . '/transient/xliff';
       if (!is_dir($uploadDir)) {
           GeneralUtility::mkdir_deep($uploadDir);
       }

       // Generate secure filename
       $filename = bin2hex(random_bytes(16)) . '.xlf';
       $filepath = $uploadDir . '/' . $filename;

       // Move uploaded file
       $file->moveTo($filepath);

       // Set restrictive permissions
       chmod($filepath, 0640);

       return $filepath;
   }

Security Considerations
=======================

XML Bomb Attacks
----------------

The parser is resilient to XML bomb attacks due to streaming:

- **Billion laughs attack:** Entity expansion blocked by LIBXML_NONET
- **Quadratic blowup:** No full DOM in memory, constant memory usage
- **External entity expansion:** Network access disabled

**Result:** DoS via malicious XLIFF files is effectively mitigated.

SSRF Prevention
---------------

LIBXML_NONET prevents Server-Side Request Forgery via XXE:

- Blocks HTTP/HTTPS external entity loading
- Blocks FTP external entity loading
- Blocks PHP wrapper access (php://, file://, data://)
- Prevents internal network probing

**Result:** Internal network resources protected from XXE-based SSRF.

File Disclosure
---------------

XXE-based file disclosure is blocked:

- No file:// protocol access
- No /etc/passwd reading
- No configuration file access
- No source code disclosure

**Result:** Server file system protected from unauthorized access.

Reporting Security Issues
==========================

If you discover a security vulnerability in the XLIFF Streaming Parser extension:

**DO NOT** open a public GitHub issue.

**Contact:** security@netresearch.de

**Include:**
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact assessment
   - Suggested fix (if available)

We take security seriously and will respond promptly to verified reports.

Security Updates
================

Subscribe to security updates:

- Watch the GitHub repository: https://github.com/netresearch/t3x-nr-xliff-streaming
- Monitor TYPO3 Security Advisories: https://typo3.org/help/security-advisories
- Enable Composer security advisories: https://github.com/Roave/SecurityAdvisories

.. versionadded:: 1.0.0
   Initial release includes comprehensive XXE protection via LIBXML_NONET.

Compliance
==========

The extension follows security best practices:

**OWASP Top 10:**
   - Protects against A05:2021 - Security Misconfiguration
   - Protects against A04:2021 - Insecure Design (XXE)

**CWE Coverage:**
   - CWE-611: Improper Restriction of XML External Entity Reference
   - CWE-776: Improper Restriction of Recursive Entity References
   - CWE-918: Server-Side Request Forgery (SSRF)

**TYPO3 Security:**
   - Follows TYPO3 Security Guidelines
   - Uses TYPO3 security APIs
   - No known CVEs

Frequently Asked Questions
===========================

**Q: Is the XXE protection enabled by default?**

A: Yes. LIBXML_NONET is always enforced, no configuration needed.

**Q: Can I disable XXE protection for trusted sources?**

A: No. Security protections are always active and cannot be disabled.
   This is intentional to prevent misconfiguration vulnerabilities.

**Q: Does XXE protection impact performance?**

A: No. LIBXML_NONET has negligible performance impact and is recommended
   by PHP security best practices.

**Q: Are there any known XXE bypasses?**

A: No known bypasses exist when LIBXML_NONET is properly enforced.
   The extension includes comprehensive test coverage to verify protection.

**Q: What if I need to process XLIFF with external entities?**

A: External entities are a security risk and should not be used.
   All translation data should be self-contained within the XLIFF file.

Next Steps
==========

- :ref:`integration` - Learn how to integrate securely
- :ref:`api` - Review the complete API reference
- GitHub Issues: https://github.com/netresearch/t3x-nr-xliff-streaming/issues
