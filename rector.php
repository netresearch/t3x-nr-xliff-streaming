<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

$configure = require_once __DIR__ . '/.Build/vendor/netresearch/typo3-ci-workflows/config/rector/rector.php';

return static function (RectorConfig $rectorConfig) use ($configure): void {
    // Shared org base config: paths (with Tests/), code-quality sets, rule
    // skips, the package's ergebnis-free phpstan-rector.neon, and the PHP and
    // TYPO3 level sets for the lowest supported versions (composer.json:
    // php ^8.2, typo3/cms-core ^13.4 || ^14.3). A newer TYPO3 level set could
    // emit code 13.4 cannot run.
    $configure($rectorConfig, __DIR__, [
        'tests' => true,
        'php'   => '8.2',
        'typo3' => 13,
    ]);

    // NAMING is not part of the shared base set but was active here before,
    // so the code is already conformant — keep it to avoid regressing.
    $rectorConfig->sets([
        SetList::NAMING,
    ]);
};
