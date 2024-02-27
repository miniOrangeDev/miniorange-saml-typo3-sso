<?php

use Miniorange\Sp\Controller\BesamlController;

/**
 * Definitions for modules provided by EXT:examples
 */
return [
    'tools_sp' => [
        'parent' => 'tools',
        'position' => [],
        'access' => 'user,group',
        'workspaces' => 'live',
        'iconIdentifier' => 'sp-plugin-bekey',
        'path' => 'module/tools/besamlkey',
        'labels' => 'LLL:EXT:sp/Resources/Private/Language/locallang_bekey.xlf',
        'extensionName' => 'sp',
        'controllerActions' => [
            BesamlController::class => 'request',
        ],
    ]
];