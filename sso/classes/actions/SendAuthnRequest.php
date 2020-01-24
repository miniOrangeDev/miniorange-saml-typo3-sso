<?php

namespace Miniorange\classes\actions;


use Miniorange\classes\AuthnRequest;
use Miniorange\helper\Constants;
use Miniorange\helper\Exception\NoIdentityProviderConfiguredException;
use Miniorange\helper\PluginSettings;
use Miniorange\helper\Utilities;

class SendAuthnRequest
{
    /**
     * Execute function to execute the classes function.
     * @throws \Exception
     * @throws NoIdentityProviderConfiguredException
     */
    public static function execute()
    {   
        $pluginSettings = PluginSettings::getPluginSettings();

        if(!Utilities::isSPConfigured()) throw new NoIdentityProviderConfiguredException();

        $relayState = isset($_REQUEST['RelayState']) ? $_REQUEST['RelayState'] : '/';

        //generate the saml request
        
        $samlRequest = (new AuthnRequest($pluginSettings->getAcsUrl(), $pluginSettings->getSpEntityId()
            ,$pluginSettings->getSamlLoginUrl(),$pluginSettings->getForceAuthentication(),
            $pluginSettings->getLoginBindingType(), $pluginSettings->getAssertionSigned(),
            $pluginSettings->getResponseSigned()))->build();

        $bindingType = $pluginSettings->getLoginBindingType();
        // send saml request over
        if(empty($bindingType)
            || $bindingType == Constants::HTTP_REDIRECT)
            return (new HttpAction())->sendHTTPRedirectRequest($samlRequest,$relayState,$pluginSettings->getSamlLoginUrl());
        else
            (new HttpAction())->sendHTTPPostRequest($samlRequest,$relayState,$pluginSettings->getSamlLoginUrl());
    }
}