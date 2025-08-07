<?php

defined('TYPO3') or die();

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Information\Typo3Version;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.enforceContentSecurityPolicy'] = false;
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'] = ['idp_name', 'RelayState', 'option', 'SAMLRequest', 'SAMLResponse', 'SigAlg', 'Signature', 'type', 'app', 'code', 'state', 'logintype'];


call_user_func(
    function () {
        $pluginNameBesaml = "Besaml";
        $pluginNameFesaml = 'Fesaml';
        $pluginNameResponse = 'Response';
        $version = new Typo3Version();
        if (version_compare($version, '10.0.0', '>=')) {
            $extensionName = 'sp';
            $cache_actions_besaml = [Miniorange\Sp\Controller\BesamlController::class => 'request'];
            $cache_actions_fesaml = [Miniorange\Sp\Controller\FesamlController::class => 'request'];
            $non_cache_actions_fesaml = [Miniorange\Sp\Controller\FesamlController::class => 'control'];
            $cache_actions_response = [Miniorange\Sp\Controller\ResponseController::class => 'response'];
        } else {
            $extensionName = 'Miniorange.sp';
            $cache_actions_besaml = ['Besaml' => 'request'];
            $cache_actions_fesaml = ['Fesaml' => 'request'];
            $non_cache_actions_fesaml = ['Fesaml' => 'control'];
            $cache_actions_response = ['Response' => 'response'];
        }

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            $extensionName,
            $pluginNameBesaml,
            [
                'Besaml' => 'request',
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            $extensionName,
            $pluginNameFesaml,
            $cache_actions_fesaml,
            // non-cacheable actions
            $non_cache_actions_fesaml
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            $extensionName,
            $pluginNameResponse,
            $cache_actions_response
        );


        // wizards
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    "mod.wizards.newContentElement.wizardItems.plugins {
                elements {
            fesaml {
                iconIdentifier = sp-extension-icon
                title = Fesaml
                description = For Sending Request
                        tt_content_defValues {
                            CType = list
                    list_type = {$extensionName}_fesaml
                        }
                    }
            response {
                iconIdentifier = sp-extension-icon
                title = Response
                description = For Handling Response 
                        tt_content_defValues {
                            CType = list
                    list_type = {$extensionName}_response
                        }
                    }
                }
                show = *
    }"
        );

        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        $iconRegistry->registerIcon(
            'sp-plugin-fesaml',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:sp/Resources/Public/Icons/Extension.png']
        );
        $iconRegistry->registerIcon(
            'sp-plugin-response',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:sp/Resources/Public/Icons/Extension.png']
        );
        $iconRegistry->registerIcon(
            'sp-plugin-bekey',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:sp/Resources/Public/Icons/Extension.png']
        );

    }
);