<?php

namespace Miniorange\Sp\Helper\Actions;

use Exception;
use Miniorange\Sp\Helper\SamlResponse;
use Miniorange\Sp\Helper\Exception\InvalidAudienceException;
use Miniorange\Sp\Helper\Exception\InvalidDestinationException;
use Miniorange\Sp\Helper\Exception\InvalidIssuerException;
use Miniorange\Sp\Helper\Exception\InvalidSamlStatusCodeException;
use Miniorange\Sp\Helper\Exception\InvalidSignatureInResponseException;
use Miniorange\Sp\Helper\Lib\XMLSecLibs\XMLSecurityKey;
use Miniorange\Sp\Helper\PluginSettings;
use Miniorange\Sp\Helper\SAMLUtilities;
use Miniorange\Sp\Controller\FesamlController;

/**
 * Handles processing of SAML Responses from the IDP. Process the SAML Response
 * from the IDP and detect if it's a valid response from the IDP. Validate the
 * certificates and the SAML attributes and Update existing user attributes
 * and groups if necessary. Log the user in.
 */
class ProcessResponseAction
{
    private $samlResponse;
    private $certfpFromPlugin;
    private $acsUrl;
    private $responseSigned;
    private $assertionSigned;
    private $issuer;
    private $spEntityId;
    private $issuerReceived;

    public function __construct(SamlResponse $samlResponseXML, $acs_url, $issuer, $sp_entity_id, $signedResponse, $signedAssertion, $x509_certificate)
    {
        //You can use dependency injection to get any class this observer may need.
        $this->samlResponse = $samlResponseXML;
        $this->acsUrl = $acs_url;
        $this->issuer = $issuer;
        $this->spEntityId = $sp_entity_id;
        $this->responseSigned = $signedResponse;
        $this->assertionSigned = $signedAssertion;
        $this->issuerReceived = $this->getIssuerFromSamlResponse();
        $this->certfpFromPlugin = XMLSecurityKey::getRawThumbprint($x509_certificate);
    }

    public function getIssuerFromSamlResponse()
    {
        return current($this->samlResponse->getAssertions())->getIssuer();
    }

    /**
     * @return mixed
     * @throws InvalidAudienceException
     * @throws InvalidDestinationException
     * @throws InvalidIssuerException
     * @throws InvalidSamlStatusCodeException
     * @throws InvalidSignatureInResponseException
     * @throws \Exception
     */
    public function execute()
    {
        error_log('In ProcessResponseAction file:');
        $this->validateStatusCode();
        $responseSignatureData = $this->samlResponse->getSignatureData();
        $assertionSignatureData = current($this->samlResponse->getAssertions())->getSignatureData();
        $this->certfpFromPlugin = iconv("UTF-8", "CP1252//IGNORE", $this->certfpFromPlugin);
        $this->certfpFromPlugin = preg_replace('/\s+/', '', $this->certfpFromPlugin);
        $this->validateSignature($responseSignatureData, $assertionSignatureData);
        error_log('validated signature');
        $this->validateDestinationURL();
        error_log('validated destinatiom');
        $this->validateResponseSignature($responseSignatureData);
        error_log('validated response signature');
        $this->validateAssertionSignature($assertionSignatureData);
        error_log('validated assertion signature');
        $this->validateIssuerAndAudience();
        error_log('validated saml response data');
    }

    /**
     * Function checks if the status coming in the SAML
     * response is SUCCESS and not a responder or
     * requester
     *
     * @param $responseSignatureData
     * @throws InvalidSamlStatusCodeException
     */
    private function validateStatusCode()
    {
        $statusCode = $this->samlResponse->getStatusCode();
        if (strpos($statusCode, 'Success') === false)
            throw new InvalidSamlStatusCodeException($statusCode, $this->samlResponse->getXML());
    }

    /**
     * Function checks if either of the SAML Response or
     * Assertion is signed or not
     *
     * @param $responseSignatureData
     * @param $assertionSignatureData
     * @throws \Exception
     */
    private function validateSignature($responseSignatureData, $assertionSignatureData)
    {
        if (!$responseSignatureData && !$assertionSignatureData) {
            throw new Exception('Neither the SAML Response nor the Assertion were signed. Please make sure that your Identity Provider sign atleast one of them.');
        }
    }

    /**
     * Function validates the Destination in the SAML Response.
     * Throws an error if the Destination doesn't match
     * with the one in the database.
     *
     * @param $currentURL
     * @throws InvalidDestinationException
     */
    private function validateDestinationURL()
    {
        $msgDestination = $this->samlResponse->getDestination();
        if ($msgDestination !== NULL && $msgDestination !== $this->acsUrl)
            throw new InvalidDestinationException($msgDestination, $this->acsUrl, $this->samlResponse);
    }

    /**
     * Function checks if the signature in the Response element
     * of the SAML response is a valid response. Throw an error
     * otherwise.
     *
     * @param $responseSignatureData
     * @throws InvalidSignatureInResponseException
     * @throws \Exception
     */
    private function validateResponseSignature($responseSignatureData)
    {
        if ($this->responseSigned != "1" || empty($responseSignatureData)) return;
        $validSignature = SAMLUtilities::processResponse($this->certfpFromPlugin, $responseSignatureData);
        if (!$validSignature) {
            throw new InvalidSignatureInResponseException($this->pluginSettings->getX509Certificate(),
                $responseSignatureData['Certificates'][0], $this->samlResponse->getXML());

        }
    }

    /**
     * Function checks if the signature in the Assertion element
     * of the SAML response is a valid response. Throw an error
     * otherwise.
     *
     * @param $assertionSignatureData
     * @throws InvalidSignatureInResponseException
     */
    private function validateAssertionSignature($assertionSignatureData)
    {
        if ($this->assertionSigned != TRUE || empty($assertionSignatureData)) return;
        $validSignature = SAMLUtilities::processResponse($this->certfpFromPlugin, $assertionSignatureData,
            $this->samlResponse);
        if (!$validSignature) {
            throw new InvalidSignatureInResponseException($this->pluginSettings->getX509Certificate(),
                $assertionSignatureData['Certificates'][0], $this->samlResponse->getXML());
        }
    }

    /**
     * Function validates the Issuer and Audience from the
     * SAML Response. THrows an error if the Issuer and
     * Audience values don't match with the one in the
     * database.
     *
     * @throws InvalidIssuerException
     * @throws InvalidAudienceException
     */
    private function validateIssuerAndAudience()
    {
        $issuer = current($this->samlResponse->getAssertions())->getIssuer();
        $audience = current(current($this->samlResponse->getAssertions())->getValidAudiences());
        if (strcmp(rtrim($this->issuer, '/'), rtrim($issuer, '/')) != 0) {
            throw new InvalidIssuerException($this->issuer, $issuer, $this->samlResponse->getXML());
        }
        if (strcmp(rtrim($audience, '/'), rtrim($this->spEntityId, '/')) != 0) {
            throw new InvalidAudienceException($this->spEntityId, $audience, $this->samlResponse->getXML());
        }
    }
}
