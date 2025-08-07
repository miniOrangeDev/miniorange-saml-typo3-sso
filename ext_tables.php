<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(
    function () {
    $version = GeneralUtility::makeInstance(Typo3Version::class);
    $isV13OrHigher = version_compare($version, '13.0.0', '>=');
    $extensionName = $isV13OrHigher || version_compare($version, '10.0.0', '>=') ? 'sp' : 'Miniorange.sp';
    $cache_actions_besaml = $isV13OrHigher || version_compare($version, '10.0.0', '>=')? [Miniorange\Sp\Controller\BesamlController::class => 'request']: ['Besaml' => 'request'];    if ($isV13OrHigher) {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['sp']['BesamlModule'] = [
            'extensionName' => $extensionName,
            'mainModuleName' => 'tools',
            'subModuleName' => 'besamlkey',
            'controllerActions' => $cache_actions_besaml,
            'access' => 'admin,user,group',
            'iconIdentifier' => 'sp-extension-icon',
            'labels' => 'LLL:EXT:sp/Resources/Private/Language/locallang_bekey.xlf',
            'position' => 'top',
        ];
        } else {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            $extensionName,
            'tools', // Make module a submodule of 'tools'
            'besamlkey', // Submodule key
            '4', // Position
            $cache_actions_besaml,
            [
                'access' => 'admin,user,group',
                'icon' => 'EXT:sp/Resources/Public/Icons/Extension.png',
                'labels' => 'LLL:EXT:sp/Resources/Private/Language/locallang_bekey.xlf',
            ]
        );
    }

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extensionName,
            'Fesaml',
            'fesaml',
        'sp-extension-icon'
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extensionName,
            'Response',
            'response',
            'sp-extension-icon'
        );

    }
);