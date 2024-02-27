<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'miniOrange SAML',
    'description' => 'Typo3 SAML Single Sign-On (SSO) extension allows your users to login to your Typo3 site by authenticating with their SAML 2.0 IdP (Identity Providers). SAML Authentication extension for Typo3 extension allows SSO with Azure AD, Azure AD B2C, Keycloak, ADFS, Okta, Shibboleth, Salesforce, GSuite / Google Apps, Office 365, SimpleSAMLphp, OpenAM, Centrify, Ping, RSA, IBM, Oracle, OneLogin, Bitium, WSO2, NetIQ, ClassLink, FusionAuth, Absorb LMS and all SAML 2.0 capable Identity Providers into your Typo3 site. Typo3 SAML SSO extension by miniOrange provides features like Attribute Mapping, Group Mapping, and Role Mapping which helps to map user data from your IdP to Typo3. You can add an SSO Login Button on both your Typo3 frontend and backend (Admin Panel) login page with our extension.',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'shy' => '',
    'icon' => 'EXT:sp/Resources/Public/Icons/miniorange.svg',
    'version' => '2.0.5',
    'autoload' => [
        'psr-4' => [
            'Miniorange\\Sp\\' => 'Classes/',
        ],
    ],
];