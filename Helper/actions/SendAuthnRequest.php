<?php

namespace Miniorange\Helper\Actions;


use Miniorange\Helper\AuthnRequest;
use Miniorange\Helper\Constants;
use Miniorange\Helper\Exception\NoIdentityProviderConfiguredException;
use Miniorange\Helper\PluginSettings;
use Miniorange\Helper\Utilities;

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