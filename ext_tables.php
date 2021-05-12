<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        if(version_compare(TYPO3_version, '10.0.0', '>=')) {
            $extensionName = 'MiniorangeSaml';
            $cache_actions_besaml = [ Miniorange\MiniorangeSaml\Controller\BesamlController::class => 'request' ];
        } else {
            $extensionName = 'Miniorange.MiniorangeSaml';
            $cache_actions_besaml = ['Besaml' => 'request'];
        }

        ExtensionUtility::registerPlugin(
            $extensionName,
            'Fesaml',
            'fesaml'
        );

        ExtensionUtility::registerPlugin(
            $extensionName,
            'Response',
            'response'
        );

        if (TYPO3_MODE === 'BE') {
            ExtensionUtility::registerModule(
                'MiniorangeSaml',
                'tools', // Make module a submodule of 'tools'
                'besaml', // Submodule key
                '', // Position
                $cache_actions_besaml,
                [
                    'access' => 'user,group',
                    'icon'   => 'EXT:miniorange_saml/Resources/Public/Icons/miniorange.png',
                    'labels' => 'LLL:EXT:miniorange_saml/Resources/Private/Language/locallang_besaml.xlf',
                ]
            );
        }

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('miniorange_saml', 'Configuration/TypoScript', 'Miniorange Saml');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_miniorangesaml_domain_model_fesaml', 'EXT:miniorange_saml/Resources/Private/Language/locallang_csh_tx_miniorangesaml_domain_model_fesaml.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_miniorangesaml_domain_model_fesaml');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_miniorangesaml_domain_model_besaml', 'EXT:miniorange_saml/Resources/Private/Language/locallang_csh_tx_miniorangesaml_domain_model_besaml.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_miniorangesaml_domain_model_besaml');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_miniorangesaml_domain_model_response', 'EXT:miniorange_saml/Resources/Private/Language/locallang_csh_tx_miniorangesaml_domain_model_response.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_miniorangesaml_domain_model_response');
    }
);
