<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'XLIFF Streaming Parser',
    'description' => 'High-performance streaming XLIFF parser supporting large translation files (10MB+) with constant memory footprint. Uses XMLReader for 30x memory reduction and 60x speed improvement over SimpleXML.',
    'category' => 'be',
    'author' => 'Netresearch DTT GmbH',
    'author_email' => 'info@netresearch.de',
    'author_company' => 'Netresearch DTT GmbH',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'php' => '8.2.0-8.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'Netresearch\\NrXliffStreaming\\' => 'Classes/',
        ],
    ],
];
