<?php

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {
         $pluginNameFesaml = 'Fesaml';
         $pluginNameResponse = 'Response';

        if (version_compare(TYPO3_version, '10.0.0', '>=')) {
            $extensionName = 'MiniorangeSaml';
            $cache_actions_fesaml = [Miniorange\MiniorangeSaml\Controller\FesamlController::class => 'request'];
            $non_cache_actions_fesaml = [Miniorange\MiniorangeSaml\Controller\FesamlController::class => 'control'];
            $cache_actions_response = [Miniorange\MiniorangeSaml\Controller\ResponseController::class => 'response'];
        }else{
            $extensionName = 'Miniorange.MiniorangeSaml';
            $cache_actions_fesaml = [ 'Fesaml' => 'request' ];
            $non_cache_actions_fesaml = [ 'Fesaml' => 'control' ];
            $cache_actions_response = [ 'Response' => 'response' ];
        }

        ExtensionUtility::configurePlugin(
            $extensionName,
            $pluginNameFesaml,
            $cache_actions_fesaml,
            // non-cacheable actions
            $non_cache_actions_fesaml
        );

        ExtensionUtility::configurePlugin(
            $extensionName,
            $pluginNameResponse,
            $cache_actions_response
        );

        // wizards
        ExtensionManagementUtility::addPageTSConfig(
        'mod {
            wizards.newContentElement.wizardItems.plugins {
                elements {
                    fesaml {
                        iconIdentifier = miniorange_saml-plugin-fesaml
                        title = LLL:EXT:miniorange_saml/Resources/Private/Language/locallang_db.xlf:tx_miniorangesaml_fesaml.name
                        description = LLL:EXT:miniorange_saml/Resources/Private/Language/locallang_db.xlf:tx_miniorangesaml_fesaml.description
                        tt_content_defValues {
                            CType = list
                            list_type = fesaml
                        }
                    }
                    response {
                        iconIdentifier = miniorange_saml-plugin-response
                        title = LLL:EXT:miniorange_saml/Resources/Private/Language/locallang_db.xlf:tx_miniorangesaml_response.name
                        description = LLL:EXT:miniorange_saml/Resources/Private/Language/locallang_db.xlf:tx_miniorangesaml_response.description
                        tt_content_defValues {
                            CType = list
                            list_type = response
                        }
                    }
                }
                show = *
             }
            }'
        );

		$iconRegistry = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
		
			$iconRegistry->registerIcon(
				'miniorange_saml-plugin-fesaml',
				BitmapIconProvider::class,
				['source' => 'EXT:miniorange_saml/Resources/Public/Icons/miniorange.png']
			);

			$iconRegistry->registerIcon(
				'miniorange_saml-plugin-response',
				BitmapIconProvider::class,
				['source' => 'EXT:miniorange_saml/Resources/Public/Icons/miniorange.png']
			);

    }
);
