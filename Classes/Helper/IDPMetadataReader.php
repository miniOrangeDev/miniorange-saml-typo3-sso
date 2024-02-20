<?php

namespace Miniorange\Sp\Helper;

use DOMElement;
use DOMNode;
use DOMDocument;
use Exception;

use Miniorange\Sp\Helper\lib\XMLSecLibs\XMLSecurityKey;
use Miniorange\Sp\Helper\lib\XMLSecLibs\XMLSecEnc;
use Miniorange\Sp\Helper\lib\XMLSecLibs\XMLSecurityDSig;
use Miniorange\Sp\Helper\lib\XMLSecLibs\Utils\XPath;

class IDPMetadataReader extends IdentityProviders
{

    private $identityProviders;
    private $serviceProviders;

    public function __construct(DOMNode $xml = NULL)
    {

        $this->identityProviders = array();
        $this->serviceProviders = array();

        $entityDescriptors = SAMLUtilities::xpQuery($xml, './saml_metadata:EntityDescriptor');

        foreach ($entityDescriptors as $entityDescriptor) {
            $idpSSODescriptor = SAMLUtilities::xpQuery($entityDescriptor, './saml_metadata:IDPSSODescriptor');

            if (isset($idpSSODescriptor) && !empty($idpSSODescriptor)) {
                array_push($this->identityProviders, new IdentityProviders($entityDescriptor));
            }
            //TODO: add sp descriptor
        }
    }

    public function getIdentityProviders()
    {
        return $this->identityProviders;
    }

    public function getServiceProviders()
    {
        return $this->serviceProviders;
    }

}