<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Information\Typo3Version;

call_user_func(
    function () {
        $version = new Typo3Version();
        if (version_compare($version, '10.0.0', '>=')) {
            $extensionName = 'sp';
            $cache_actions_besaml = [Miniorange\Sp\Controller\BesamlController::class => 'request'];
        } else {
            $extensionName = 'Miniorange.sp';
            $cache_actions_besaml = ['Besaml' => 'request'];
        }
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            $extensionName,
            'tools', // Make module a submodule of 'tools'
            'besamlkey', // Submodule key
            '4', // Position
            $cache_actions_besaml,
            [
                'access' => 'admin,user,group',
                'icon' => 'EXT:sp/Resources/Public/Icons/miniorange.png',
                'source' => 'EXT:sp/Resources/Public/Icons/miniorange.svg',
                'labels' => 'LLL:EXT:sp/Resources/Private/Language/locallang_bekey.xlf',
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extensionName,
            'Fesaml',
            'fesaml',
            'EXT:sp/Resources/Public/Icons/miniorange.svg'
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extensionName,
            'Response',
            'response',
            'EXT:sp/Resources/Public/Icons/miniorange.svg'
        );

    }
);