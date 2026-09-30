<?php

declare(strict_types=1);

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

use a9f\Fractor\Configuration\FractorConfiguration;
use a9f\Typo3Fractor\Set\Typo3LevelSetList;

// TYPO3 13.4 is the lowest supported version (composer.json), so apply the
// migrations up to TYPO3 13 and none that would drop 13.4 compatibility.
return FractorConfiguration::configure()
    ->withPaths([
        __DIR__ . '/../../Classes',
        __DIR__ . '/../../Configuration',
        __DIR__ . '/../../Tests',
    ])
    ->withSkip([
        __DIR__ . '/../../.Build',
        // Parser input: reformatting a fixture changes what the tests feed the parser.
        __DIR__ . '/../../Tests/Unit/Fixtures',
    ])
    ->withSets([
        Typo3LevelSetList::UP_TO_TYPO3_13,
    ]);
