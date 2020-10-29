<?php

/***********************************************************************************8
 * Extension Manager/Repository config file for ext: "miniorange_saml"
 ******************************************************************************8*****/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Miniorange Saml',
    'description' => 'Miniorange Saml (SSO) provides SSO/Login to your TYPO3 site with any SAML compliant Identity Provider (ACTIVE SUPPORT for IdP config). It allows users residing at SAML 2.0 capable Identity Provider to login to your TYPO3 website. We support all known IdPs – Google Apps, ADFS, Azure AD, Okta, Salesforce, Shibboleth, SimpleSAMLphp, OpenAM, Centrify, Ping, RSA, IBM, Oracle, OneLogin, Bitium, WSO2, NetIQ etc.',
    'category' => 'plugin',
    'author' => 'Miniorange',
    'author_email' => 'info@xecurify.com',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '1.3.3',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-9.6.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'Miniorange\\MiniorangeSaml\\' => 'Classes',
            "Miniorange\\Helper\\" => 'Helper'
        ]
    ],
];
