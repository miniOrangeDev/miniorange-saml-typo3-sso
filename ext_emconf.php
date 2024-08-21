<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'SAML Single Sign On SSO (Backend + Frontend)',
    'description' => 'SAML SSO for Typo3 frontend and Backend',
    'author' => 'miniOrange',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.30-13.2.1',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'shy' => '',
    'icon' => 'EXT:sp/Resources/Public/Icons/Extension.svg',
    'version' => '5.5.0',
    'state' => 'stable',
    'autoload' => [
        'psr-4' => [
            'Miniorange\\Sp\\' => 'Classes/',
        ],
    ],
];