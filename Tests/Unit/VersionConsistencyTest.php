<?php

declare(strict_types=1);

namespace Netresearch\NrXliffStreaming\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Guards the hand-maintained version surfaces against drift.
 *
 * The extension version lives in ext_emconf.php and in composer.json
 * (extra.typo3/cms.version). TYPO3 14.3 in classic mode reads only the
 * composer.json value once extra.typo3/cms carries Package.providesPackages
 * and a version (PackageManager::isComposerOnlyCapable(), deprecation
 * #108345), while TYPO3 13.4 still reads ext_emconf.php. A release bump that
 * touches only one file therefore shows a stale version on one of the two
 * lines; this test fails instead.
 *
 * The supported TYPO3 and PHP ranges are stated three times as well: in
 * composer.json, in ext_emconf.php and in the CI matrix. This repository has
 * no support-matrix page to check them against, so the test pins the three
 * sources against each other, with the CI matrix as the set actually tested.
 */
#[CoversNothing]
final class VersionConsistencyTest extends UnitTestCase
{
    private function repoRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    private function readRepoFile(string $path): string
    {
        $contents = file_get_contents($this->repoRoot() . '/' . $path);
        self::assertIsString($contents, $path . ' must be readable');

        return $contents;
    }

    /**
     * @return array<mixed>
     */
    private function composerJson(): array
    {
        $composer = json_decode($this->readRepoFile('composer.json'), true);
        self::assertIsArray($composer, 'composer.json must decode to an object');

        return $composer;
    }

    private function extEmConfValue(string $key): string
    {
        self::assertSame(
            1,
            preg_match("/'" . preg_quote($key, '/') . "'\\s*=>\\s*'([^']+)'/", $this->readRepoFile('ext_emconf.php'), $matches),
            'ext_emconf.php must declare exactly one readable "' . $key . '" value',
        );

        return $matches[1];
    }

    /**
     * The union of one matrix key over every call in ci.yml, lowest first,
     * with a leading caret removed.
     *
     * @return non-empty-list<string>
     */
    private function ciMatrix(string $key): array
    {
        preg_match_all(
            '/^\s*' . preg_quote($key, '/') . ":\\s*'([^']+)'/m",
            $this->readRepoFile('.github/workflows/ci.yml'),
            $matches,
        );
        self::assertNotSame([], $matches[1], '.github/workflows/ci.yml must set ' . $key);

        $versions = [];
        foreach ($matches[1] as $json) {
            $decoded = json_decode($json, true);
            self::assertIsArray($decoded, $key . ' in ci.yml must be a JSON array');
            foreach ($decoded as $version) {
                self::assertIsString($version);
                $versions[] = ltrim($version, '^');
            }
        }

        $versions = array_values(array_unique($versions));
        sort($versions, SORT_NATURAL);
        self::assertNotSame([], $versions);

        return $versions;
    }

    /**
     * ext_emconf.php range spanning the given matrix: lowest.0 to highest.99.
     *
     * @param non-empty-list<string> $matrix
     */
    private function emConfRangeFor(array $matrix): string
    {
        return $matrix[0] . '.0-' . $matrix[count($matrix) - 1] . '.99';
    }

    #[Test]
    public function composerJsonVersionMatchesExtEmconf(): void
    {
        $composer = $this->composerJson();
        $extra = $composer['extra'] ?? null;
        self::assertIsArray($extra);
        $typo3Cms = $extra['typo3/cms'] ?? null;
        self::assertIsArray($typo3Cms);

        self::assertSame(
            $this->extEmConfValue('version'),
            $typo3Cms['version'] ?? null,
            'composer.json extra.typo3/cms.version must match ext_emconf.php version '
            . '(TYPO3 14.3 reads the first, 13.4 the second; keep both in sync on every release bump).',
        );
    }

    #[Test]
    public function typo3RangeAgreesAcrossComposerExtEmconfAndCi(): void
    {
        $matrix = $this->ciMatrix('typo3-versions');
        $composer = $this->composerJson();
        $require = $composer['require'] ?? null;
        self::assertIsArray($require);
        $required = $require['typo3/cms-core'] ?? null;
        self::assertIsString($required);

        // The shared CI workflow narrows typo3/cms-core in composer.json to the
        // one matrix cell it installs, so inside a CI job the file says `^13.4`
        // where the repository declares `^13.4 || ^14.3`. Accept the full range
        // or exactly one of its cells, nothing else.
        $cells = array_map(static fn(string $version): string => '^' . $version, $matrix);
        self::assertContains(
            $required,
            [implode(' || ', $cells), ...$cells],
            'composer.json requires typo3/cms-core "' . $required . '", but the CI matrix tests '
            . implode(', ', $cells) . '.',
        );

        self::assertSame(
            $this->emConfRangeFor($matrix),
            $this->extEmConfValue('typo3'),
            'ext_emconf.php typo3 constraint must span exactly the TYPO3 versions the CI matrix tests.',
        );
    }

    #[Test]
    public function phpRangeAgreesAcrossComposerExtEmconfAndCi(): void
    {
        $matrix = $this->ciMatrix('php-versions');
        $composer = $this->composerJson();
        $require = $composer['require'] ?? null;
        self::assertIsArray($require);

        self::assertSame(
            '^' . $matrix[0],
            $require['php'] ?? null,
            'composer.json php constraint must start at the lowest PHP version the CI matrix tests.',
        );

        self::assertSame(
            $this->emConfRangeFor($matrix),
            $this->extEmConfValue('php'),
            'ext_emconf.php php constraint must span exactly the PHP versions the CI matrix tests.',
        );
    }
}
