<?php

/***********************************************************************************8
 * Extension Manager/Repository config file for ext: "miniorange_saml"
 ******************************************************************************8*****/

$EM_CONF[$_EXTKEY] = [
    'title' => 'SAML Single Sign-On (SSO) - SAML Authentication',
    'description' => 'Typo3 SAML Single Sign-On (SSO) extension allows your users to login to your Typo3 site by authenticating with their SAML 2.0 IdP (Identity Providers). 

SAML Authentication extension for Typo3 extension allows SSO with Azure AD, Azure AD B2C, Keycloak, ADFS, Okta, Shibboleth, Salesforce, GSuite / Google Apps, Office 365, SimpleSAMLphp, OpenAM, Centrify, Ping, RSA, IBM, Oracle, OneLogin, Bitium, WSO2, NetIQ, ClassLink, FusionAuth, Absorb LMS and all SAML 2.0 capable Identity Providers into your Typo3 site.

Typo3 SAML SSO extension by miniOrange provides features like Attribute Mapping, Group Mapping, and Role Mapping which helps to map user data from your IdP to Magento. You can add an SSO Login Button on both your Magento frontend and backend (Admin Panel) login page with our extension. 
',
    'category' => 'plugin',
    'author' => 'Miniorange',
    'author_email' => 'info@xecurify.com',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '2.0.2',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.3-11.5.27',
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
