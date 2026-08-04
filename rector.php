<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

$configure = require_once __DIR__ . '/.Build/vendor/netresearch/typo3-ci-workflows/config/rector/rector.php';

return static function (RectorConfig $rectorConfig) use ($configure): void {
    // Shared org base config: code-quality sets, rule skips, phpstan-rector.neon
    $configure($rectorConfig, __DIR__);

    // paths() replaces the shared list — re-declared to keep Tests/ in scope,
    // which the shared $projectRoot default leaves out.
    $rectorConfig->paths(array_merge(
        [
            __DIR__ . '/Classes',
            __DIR__ . '/Configuration',
            __DIR__ . '/Resources',
            __DIR__ . '/Tests',
        ],
        glob(__DIR__ . '/ext_*.php') ?: [],
    ));

    // NAMING is not part of the shared base set but was active here before,
    // so the code is already conformant — keep it to avoid regressing.
    $rectorConfig->sets([
        SetList::NAMING,
    ]);
};
