<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Miniorange.MiniorangeSaml',
            'Fesaml',
            [
                'Fesaml' => 'request'
            ],
            // non-cacheable actions
            [
                'Fesaml' => 'control'
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Miniorange.MiniorangeSaml',
            'Response',
            [
                'Response' => 'response'
            ],
            // non-cacheable actions
            [
                'Fesaml' => '',
                'Besaml' => '',
                'Response' => ''
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Miniorange.MiniorangeSaml',
            'Logout',
            [
                'Logout' => 'check'
            ],
            [
                'Response' => ''
            ]
        );

    // wizards
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
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
                     Logout {
                        iconIdentifier = miniorange_saml-plugin-logout
                        title = LLL:EXT:miniorange_saml/Resources/Private/Language/locallang_db.xlf:tx_MiniorangeSaml_logout.name
                        description = LLL:EXT:miniorange_saml/Resources/Private/Language/locallang_db.xlf:tx_MiniorangeSaml_logout.description
                        tt_content_defValues {
                            CType = list
                            list_type = Logout
                        }
                    }
                }
                show = *
            }
       }'
    );
		$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
		
			$iconRegistry->registerIcon(
				'miniorange_saml-plugin-fesaml',
				\TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
				['source' => 'EXT:miniorange_saml/Resources/Public/Icons/miniorange.png']
			);
		
			$iconRegistry->registerIcon(
				'miniorange_saml-plugin-response',
				\TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
				['source' => 'EXT:miniorange_saml/Resources/Public/Icons/miniorange.png']
			);

        $iconRegistry->registerIcon(
            'miniorange_saml-plugin-logout',
            \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
            ['source' => 'EXT:miniorange_saml/Resources/Public/Icons/miniorange.png']
        );

    }
);
