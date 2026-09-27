<?php

declare(strict_types=1);

namespace Netresearch\NrXliffStreaming\Tests\Unit;

use Composer\Semver\Intervals;
use Composer\Semver\VersionParser;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
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
 * sources against each other. The CI side is the set of versions that at
 * least one matrix cell tests: every php-versions x typo3-versions pair of
 * each job in ci.yml, minus the pairs its matrix-exclude removes.
 *
 * Each source is read the way its consumer reads it: ext_emconf.php is
 * included with $_EXTKEY set, as TYPO3's PackageManager does, ci.yml is
 * parsed as YAML and its matrix inputs as JSON, as GitHub Actions does, and
 * composer constraints are compared as version intervals, as composer does.
 */
#[CoversNothing]
final class VersionConsistencyTest extends UnitTestCase
{
    private function repoRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * @return array<mixed>
     */
    private function composerJson(): array
    {
        $contents = file_get_contents($this->repoRoot() . '/composer.json');
        self::assertIsString($contents, 'composer.json must be readable');
        $composer = json_decode($contents, true);
        self::assertIsArray($composer, 'composer.json must decode to an object');

        return $composer;
    }

    /**
     * @return array<mixed>
     */
    private function composerTypo3Extra(): array
    {
        $extra = $this->composerJson()['extra'] ?? null;
        self::assertIsArray($extra);
        $typo3Cms = $extra['typo3/cms'] ?? null;
        self::assertIsArray($typo3Cms, 'composer.json must carry extra.typo3/cms');

        return $typo3Cms;
    }

    private function composerRequire(string $package): string
    {
        $require = $this->composerJson()['require'] ?? null;
        self::assertIsArray($require);
        $constraint = $require[$package] ?? null;
        self::assertIsString($constraint, 'composer.json must require ' . $package);

        return $constraint;
    }

    /**
     * The array ext_emconf.php assigns to $EM_CONF[$_EXTKEY], obtained the way
     * PackageManager::getExtensionEmConf() obtains it: by including the file
     * with $_EXTKEY set. Comments, duplicate keys and expressions resolve as
     * PHP resolves them.
     *
     * @return array<mixed>
     */
    private function extEmConf(): array
    {
        $extensionKey = $this->composerTypo3Extra()['extension-key'] ?? null;
        self::assertIsString($extensionKey, 'composer.json must declare extra.typo3/cms.extension-key');

        $include = static function (string $path, string $_EXTKEY): mixed {
            $EM_CONF = null;
            include $path;
            // Read back through get_defined_vars(): static analysis cannot see
            // that the include assigns $EM_CONF, and would take it as null.
            $emConf = get_defined_vars()['EM_CONF'] ?? null;

            return is_array($emConf) ? ($emConf[$_EXTKEY] ?? null) : null;
        };
        $emConf = $include($this->repoRoot() . '/ext_emconf.php', $extensionKey);
        self::assertIsArray($emConf, 'ext_emconf.php must assign an array to $EM_CONF[$_EXTKEY]');

        return $emConf;
    }

    private function extEmConfVersion(): string
    {
        $version = $this->extEmConf()['version'] ?? null;
        self::assertIsString($version, 'ext_emconf.php must declare a version');

        return $version;
    }

    private function extEmConfDepends(string $key): string
    {
        $constraints = $this->extEmConf()['constraints'] ?? null;
        self::assertIsArray($constraints);
        $depends = $constraints['depends'] ?? null;
        self::assertIsArray($depends);
        $value = $depends[$key] ?? null;
        self::assertIsString($value, 'ext_emconf.php must declare a ' . $key . ' dependency');

        return $value;
    }

    /**
     * The values of one matrix axis ("php" or "typo3") that at least one
     * non-excluded cell tests, over every job in ci.yml that sets both axes.
     * An exclude entry removes every cell whose values match all its keys,
     * as GitHub Actions applies `strategy.matrix.exclude`.
     *
     * @return non-empty-list<string> natural order, lowest first
     */
    private function ciTested(string $axis): array
    {
        $workflow = Yaml::parseFile($this->repoRoot() . '/.github/workflows/ci.yml');
        self::assertIsArray($workflow);
        $jobs = $workflow['jobs'] ?? null;
        self::assertIsArray($jobs, 'ci.yml must define jobs');

        $tested = [];
        $matrixJobs = 0;
        foreach ($jobs as $job) {
            $with = is_array($job) ? ($job['with'] ?? null) : null;
            if (!is_array($with) || !isset($with['php-versions'], $with['typo3-versions'])) {
                continue;
            }

            ++$matrixJobs;
            $axes = [
                'php' => $this->stringList($with['php-versions'], 'php-versions'),
                'typo3' => $this->stringList($with['typo3-versions'], 'typo3-versions'),
            ];
            $excludes = isset($with['matrix-exclude'])
                ? $this->jsonList($with['matrix-exclude'], 'matrix-exclude')
                : [];

            foreach ($axes['php'] as $php) {
                foreach ($axes['typo3'] as $typo3) {
                    $cell = ['php' => $php, 'typo3' => $typo3];
                    if (!$this->isExcluded($cell, $excludes)) {
                        $tested[] = $cell[$axis];
                    }
                }
            }
        }

        self::assertGreaterThan(0, $matrixJobs, 'ci.yml must have a job that sets php-versions and typo3-versions');
        self::assertNotSame([], $tested, 'matrix-exclude removes every cell of the CI matrix');

        $tested = array_values(array_unique($tested));
        sort($tested, SORT_NATURAL);

        return $tested;
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $json, string $input): array
    {
        $values = [];
        foreach ($this->jsonList($json, $input) as $value) {
            self::assertIsString($value, $input . ' in ci.yml must list strings');
            $values[] = $value;
        }

        return $values;
    }

    /**
     * @return list<mixed>
     */
    private function jsonList(mixed $json, string $input): array
    {
        self::assertIsString($json, $input . ' in ci.yml must be a JSON string');
        $decoded = json_decode($json, true);
        self::assertIsArray($decoded, $input . ' in ci.yml must decode to a JSON array');
        self::assertTrue(array_is_list($decoded), $input . ' in ci.yml must decode to a JSON array');

        return $decoded;
    }

    /**
     * @param array{php: mixed, typo3: mixed} $cell
     * @param list<mixed> $excludes
     */
    private function isExcluded(array $cell, array $excludes): bool
    {
        foreach ($excludes as $exclude) {
            self::assertIsArray($exclude, 'matrix-exclude entries must be objects');
            $matches = true;
            foreach ($exclude as $key => $value) {
                if (!array_key_exists($key, $cell) || $cell[$key] !== $value) {
                    $matches = false;
                    break;
                }
            }

            if ($matches && $exclude !== []) {
                return true;
            }
        }

        return false;
    }

    private function sameConstraint(string $a, string $b): bool
    {
        $versionParser = new VersionParser();
        $constraint = $versionParser->parseConstraints($a);
        $other = $versionParser->parseConstraints($b);

        return Intervals::isSubsetOf($constraint, $other) && Intervals::isSubsetOf($other, $constraint);
    }

    /**
     * ext_emconf.php range spanning the given versions: lowest.0 to highest.99.
     *
     * @param non-empty-list<string> $versions major.minor, optionally with a leading caret
     */
    private function emConfRangeFor(array $versions): string
    {
        return ltrim($versions[0], '^') . '.0-' . ltrim($versions[count($versions) - 1], '^') . '.99';
    }

    #[Test]
    public function composerJsonVersionMatchesExtEmconf(): void
    {
        self::assertSame(
            $this->extEmConfVersion(),
            $this->composerTypo3Extra()['version'] ?? null,
            'composer.json extra.typo3/cms.version must match ext_emconf.php version '
            . '(TYPO3 14.3 reads the first, 13.4 the second; keep both in sync on every release bump).',
        );
    }

    #[Test]
    public function typo3RangeAgreesAcrossComposerExtEmconfAndCi(): void
    {
        $tested = $this->ciTested('typo3');
        $required = $this->composerRequire('typo3/cms-core');

        // Shared CI and `runTests.sh -t` narrow typo3/cms-core in composer.json
        // to the one matrix cell they install, before this test runs. Inside
        // such a run the file says `^13.4` where the repository declares
        // `^13.4 || ^14.3`, so a single tested cell has to be accepted.
        // What this no longer detects: a single-cell constraint committed by
        // accident (`^14.3` alone) passes here. That has to be caught in review
        // or at release.
        $accepted = [implode(' || ', $tested), ...$tested];
        $matches = array_filter(
            $accepted,
            fn(string $constraint): bool => $this->sameConstraint($required, $constraint),
        );
        self::assertNotSame(
            [],
            $matches,
            'composer.json requires typo3/cms-core "' . $required . '", which is neither the range the CI matrix tests ("'
            . $accepted[0] . '") nor a single one of its cells.',
        );

        self::assertSame(
            $this->emConfRangeFor($tested),
            $this->extEmConfDepends('typo3'),
            'ext_emconf.php typo3 constraint must span exactly the TYPO3 versions the CI matrix tests.',
        );
    }

    #[Test]
    public function phpRangeAgreesAcrossComposerExtEmconfAndCi(): void
    {
        $tested = $this->ciTested('php');

        self::assertTrue(
            $this->sameConstraint($this->composerRequire('php'), '^' . $tested[0]),
            'composer.json php constraint "' . $this->composerRequire('php')
            . '" must be ^' . $tested[0] . ', starting at the lowest PHP version the CI matrix tests.',
        );

        self::assertSame(
            $this->emConfRangeFor($tested),
            $this->extEmConfDepends('php'),
            'ext_emconf.php php constraint must span exactly the PHP versions the CI matrix tests.',
        );
    }
}
