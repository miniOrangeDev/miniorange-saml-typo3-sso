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

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extensionName,
            'Fesaml',
            'fesaml'
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extensionName,
            'Response',
            'response'
        );

    }
);
