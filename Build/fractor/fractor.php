<?php

declare(strict_types=1);

use a9f\Fractor\Configuration\FractorConfiguration;

return static function (FractorConfiguration $config): void {
    // Define paths to refactor
    $config->paths([
        __DIR__ . '/../../Classes',
        __DIR__ . '/../../Tests',
    ]);

    // Skip paths
    $config->skip([
        __DIR__ . '/../../.Build',
    ]);

    // PHP version
    $config->phpVersion('8.2');

    // Import all TYPO3 rulesets
    $config->import(__DIR__ . '/../../.Build/vendor/a9f/fractor-typo3/config/fractor.php');
};
