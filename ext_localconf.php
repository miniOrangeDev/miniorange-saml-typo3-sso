<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Miniorange.MiniorangeSaml',
            'Fekey',
            [
                'Fesaml' => 'print'
            ],
            // non-cacheable actions
            [
                'Fesaml' => 'control'
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'Miniorange.MiniorangeSaml',
            'Responsekey',
            [
                'Response' => 'check'
            ],
            // non-cacheable actions
            [
                'Fesaml' => '',
                'Besaml' => '',
                'Response' => ''
            ]
        );

    // wizards
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        'mod {
            wizards.newContentElement.wizardItems.plugins {
                elements {
                    fekey {
                        iconIdentifier = miniorange_saml-plugin-fekey
                        title = LLL:EXT:miniorange_saml/Resources/Private/Language/locallang_db.xlf:tx_ekey_fekey.name
                        description = LLL:EXT:miniorange_saml/Resources/Private/Language/locallang_db.xlf:tx_ekey_fekey.description
                        tt_content_defValues {
                            CType = list
                            list_type = ekey_fekey
                        }
                    }
                    responsekey {
                        iconIdentifier = miniorange_saml-plugin-responsekey
                        title = LLL:EXT:miniorange_saml/Resources/Private/Language/locallang_db.xlf:tx_ekey_responsekey.name
                        description = LLL:EXT:miniorange_saml/Resources/Private/Language/locallang_db.xlf:tx_ekey_responsekey.description
                        tt_content_defValues {
                            CType = list
                            list_type = ekey_responsekey
                        }
                    }
                }
                show = *
            }
       }'
    );
		$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
		
			$iconRegistry->registerIcon(
				'miniorange_saml-plugin-fekey',
				\TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
				['source' => 'EXT:miniorange_saml/Resources/Public/Icons/miniorange.png']
			);
		
			$iconRegistry->registerIcon(
				'miniorange_saml-plugin-responsekey',
				\TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
				['source' => 'EXT:miniorange_saml/Resources/Public/Icons/miniorange.png']
			);
		
    }
);
