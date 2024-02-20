<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('sp', 'Configuration/TypoScript', '');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_sp_domain_model_fesaml', 'EXT:sp/Resources/Private/Language/locallang_csh_tx_sp_domain_model_fesaml.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_sp_domain_model_fesaml');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_sp_domain_model_besaml', 'EXT:sp/Resources/Private/Language/locallang_csh_tx_sp_domain_model_besaml.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_sp_domain_model_besaml');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_sp_domain_model_response', 'EXT:sp/Resources/Private/Language/locallang_csh_tx_sp_domain_model_response.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_sp_domain_model_response');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_sp_domain_model_logout', 'EXT:sp/Resources/Private/Language/locallang_csh_tx_sp_domain_model_logout.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_sp_domain_model_logout');