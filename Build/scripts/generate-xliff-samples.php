<?php
declare(strict_types=1);

/**
 * Generate XLIFF sample files for performance testing
 */

function generateXliffFile(string $outputPath, int $transUnitCount): void
{
    $file = fopen($outputPath, 'w');

    // Write header
    fwrite($file, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
    fwrite($file, '<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">' . "\n");
    fwrite($file, '  <file source-language="en" target-language="de" datatype="plaintext">' . "\n");
    fwrite($file, '    <body>' . "\n");

    // Generate trans-units
    for ($i = 1; $i <= $transUnitCount; $i++) {
        $id = sprintf('component.type.placeholder_%d', $i);
        $source = sprintf('This is source text number %d with some additional content to make it realistic. Lorem ipsum dolor sit amet, consectetur adipiscing elit.', $i);
        $target = sprintf('Dies ist Quelltext Nummer %d mit zusätzlichem Inhalt, um es realistisch zu machen. Lorem ipsum dolor sit amet, consectetur adipiscing elit.', $i);

        fwrite($file, sprintf(
            '      <trans-unit id="%s">' . "\n" .
            '        <source>%s</source>' . "\n" .
            '        <target>%s</target>' . "\n" .
            '      </trans-unit>' . "\n",
            htmlspecialchars($id, ENT_XML1),
            htmlspecialchars($source, ENT_XML1),
            htmlspecialchars($target, ENT_XML1)
        ));

        // Progress indicator
        if ($i % 10000 === 0) {
            echo "Generated {$i}/{$transUnitCount} trans-units...\n";
        }
    }

    // Write footer
    fwrite($file, '    </body>' . "\n");
    fwrite($file, '  </file>' . "\n");
    fwrite($file, '</xliff>' . "\n");

    fclose($file);

    $fileSize = filesize($outputPath);
    $fileSizeMB = round($fileSize / 1024 / 1024, 2);
    echo "Generated: {$outputPath}\n";
    echo "Size: {$fileSizeMB} MB ({$fileSize} bytes)\n";
    echo "Trans-units: {$transUnitCount}\n\n";
}

// Create output directory
$outputDir = __DIR__ . '/../../Tests/Fixtures/Performance';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

echo "Generating XLIFF sample files...\n\n";

// ~30KB file
echo "1. Generating ~30KB file...\n";
generateXliffFile($outputDir . '/sample-30kb.xlf', 100);

// ~1MB file
echo "2. Generating ~1MB file...\n";
generateXliffFile($outputDir . '/sample-1mb.xlf', 3000);

// ~30MB file
echo "3. Generating ~30MB file...\n";
generateXliffFile($outputDir . '/sample-30mb.xlf', 100000);

// ~100MB file
echo "4. Generating ~100MB file...\n";
generateXliffFile($outputDir . '/sample-100mb.xlf', 330000);

echo "All sample files generated successfully!\n";
