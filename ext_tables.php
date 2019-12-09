<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Miniorange.MiniorangeSaml',
            'Fekey',
            'fename'
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'Miniorange.MiniorangeSaml',
            'Responsekey',
            'response'
        );

        if (TYPO3_MODE === 'BE') {

            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                'Miniorange.MiniorangeSaml',
                'tools', // Make module a submodule of 'tools'
                'bekey', // Submodule key
                '', // Position
                [
                    'Besaml' => 'request',
                    
                ],
                [
                    'access' => 'user,group',
                    'icon'   => 'EXT:miniorange_saml/Resources/Public/Icons/miniorange.png',
                    'labels' => 'LLL:EXT:miniorange_saml/Resources/Private/Language/locallang_bekey.xlf',
                ]
            );

        }

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('miniorange_saml', 'Configuration/TypoScript', 'etitle');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_ekey_domain_model_fesaml', 'EXT:miniorange_saml/Resources/Private/Language/locallang_csh_tx_ekey_domain_model_fesaml.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_ekey_domain_model_fesaml');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_ekey_domain_model_besaml', 'EXT:miniorange_saml/Resources/Private/Language/locallang_csh_tx_ekey_domain_model_besaml.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_ekey_domain_model_besaml');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_ekey_domain_model_response', 'EXT:miniorange_saml/Resources/Private/Language/locallang_csh_tx_ekey_domain_model_response.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_ekey_domain_model_response');

    }
);
