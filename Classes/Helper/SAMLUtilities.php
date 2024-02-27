<?php

namespace Miniorange\Sp\Helper;

use DOMElement;
use DOMNode;
use DOMDocument;
use Exception;

use Miniorange\Sp\Helper\Lib\XMLSecLibs\XMLSecurityKey;
use Miniorange\Sp\Helper\Lib\XMLSecLibs\XMLSecEnc;
use Miniorange\Sp\Helper\Lib\XMLSecLibs\XMLSecurityDSig;

/** @todo - optimize this class */
class SAMLUtilities extends Utilities
{

    public static function createLogoutRequest($nameId, $sessionIndex, $issuer, $destination, $slo_binding_type = 'HttpRedirect')
    {
        $requestXmlStr = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<samlp:LogoutRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="' . self::generateID() .
            '" IssueInstant="' . self::generateTimestamp() .
            '" Version="2.0" Destination="' . $destination . '">
						<saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">' . $issuer . '</saml:Issuer>
						<saml:NameID xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">' . $nameId . '</saml:NameID>';
        if (!empty($sessionIndex)) {
            $requestXmlStr .= '<samlp:SessionIndex>' . $sessionIndex . '</samlp:SessionIndex>';
        }
        $requestXmlStr .= '</samlp:LogoutRequest>';

        if (empty($slo_binding_type) || $slo_binding_type == 'HttpRedirect') {
            $deflatedStr = gzdeflate($requestXmlStr);
            $base64EncodedStr = base64_encode($deflatedStr);
            $urlEncoded = urlencode($base64EncodedStr);
            $requestXmlStr = $urlEncoded;
        }
        return $requestXmlStr;
    }

    public static function generateID()
    {
        return '_' . self::stringToHex(self::generateRandomBytes(21));
    }

    public static function stringToHex($bytes)
    {
        $ret = '';
        for ($i = 0; $i < strlen($bytes); $i++) {
            $ret .= sprintf('%02x', ord($bytes[$i]));
        }
        return $ret;
    }

    public static function generateRandomBytes($length)
    {
        return openssl_random_pseudo_bytes($length);
    }

    public static function generateTimestamp($instant = NULL)
    {
        if ($instant === NULL) {
            $instant = time();
        }
        return gmdate('Y-m-d\TH:i:s\Z', $instant);
    }

    /**
     * Insert a Signature-node.
     *
     * @param XMLSecurityKey $key The key we should use to sign the message.
     * @param array $certificates The certificates we should add to the signature node.
     * @param DOMElement $root The XML node we should sign.
     * @param DomNode $insertBefore The XML element we should insert the signature element before.
     * @throws Exception
     */
    public static function insertSignature(
        XMLSecurityKey $key,
        array          $certificates,
        DOMElement     $root,
        DomNode        $insertBefore = NULL
    )
    {
        $objXMLSecDSig = new XMLSecurityDSig();
        $objXMLSecDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

        switch ($key->type) {
            case XMLSecurityKey::RSA_SHA256:
                $type = XMLSecurityDSig::SHA256;
                break;
            case XMLSecurityKey::RSA_SHA384:
                $type = XMLSecurityDSig::SHA384;
                break;
            case XMLSecurityKey::RSA_SHA512:
                $type = XMLSecurityDSig::SHA512;
                break;
            default:
                $type = XMLSecurityDSig::SHA1;
        }

        $objXMLSecDSig->addReferenceList(
            array($root),
            $type,
            array('http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N),
            array('id_name' => 'ID', 'overwrite' => FALSE)
        );

        $objXMLSecDSig->sign($key);

        foreach ($certificates as $certificate) {
            $objXMLSecDSig->add509Cert($certificate, TRUE);
        }
        $objXMLSecDSig->insertSignature($root, $insertBefore);
    }

    /**
     * @param $samlRequest
     * @param $sendRelayState
     * @param $idpUrl
     * @throws \Exception
     */
    public static function sendHTTPRedirectRequest($samlRequest, $sendRelayState, $idpUrl)
    {
        $samlRequest = 'SAMLRequest=' . $samlRequest . '&RelayState=' . urlencode($sendRelayState) . '&SigAlg=' . urlencode(XMLSecurityKey::RSA_SHA256);
        $param = ['type' => 'private'];
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, $param);
        $certFilePath = file_get_contents(__DIR__ . '/resources/sp-cert.key');
        $key->loadKey($certFilePath);
        $signature = $key->signData($samlRequest);
        $signature = base64_encode($signature);
        $redirect = $idpUrl;
        $redirect .= strpos($idpUrl, '?') !== false ? '&' : '?';
        $redirect .= $samlRequest . '&Signature=' . urlencode($signature);
        if (isset($_REQUEST)) {
            header('Location:' . $redirect);
            die();
        }
    }

    public static function postSAMLRequest($url, $samlRequestXML, $relayState)
    {
        echo "<html><head><script src='https://code.jquery.com/jquery-1.11.3.min.js'></script><script type=\"text/javascript\">$(function(){document.forms['saml-request-form'].submit();});</script></head><body>Please wait...<form action=\"" . $url . "\" method=\"post\" id=\"saml-request-form\"><input type=\"hidden\" name=\"SAMLRequest\" value=\"" . $samlRequestXML . "\" /><input type=\"hidden\" name=\"RelayState\" value=\"" . htmlentities($relayState) . "\" /></form></body></html>";
        exit();
    }

    public static function postSAMLResponse($url, $samlResponseXML, $relayState)
    {
        echo "<html><head><script src='https://code.jquery.com/jquery-1.11.3.min.js'></script><script type=\"text/javascript\">$(function(){document.forms['saml-request-form'].submit();});</script></head><body>Please wait...<form action=\"" . $url . "\" method=\"post\" id=\"saml-request-form\"><input type=\"hidden\" name=\"SAMLResponse\" value=\"" . $samlResponseXML . "\" /><input type=\"hidden\" name=\"RelayState\" value=\"" . htmlentities($relayState) . "\" /></form></body></html>";
        exit();
    }

    public static function parseNameId(DOMElement $xml)
    {
        $ret = array('Value' => trim($xml->textContent));

        foreach (array('NameQualifier', 'SPNameQualifier', 'Format') as $attr) {
            if ($xml->hasAttribute($attr)) {
                $ret[$attr] = $xml->getAttribute($attr);
            }
        }

        return $ret;
    }

    public static function xsDateTimeToTimestamp($time)
    {
        $matches = array();

        // We use a very strict regex to parse the timestamp.
        $regex = '/^(\\d\\d\\d\\d)-(\\d\\d)-(\\d\\d)T(\\d\\d):(\\d\\d):(\\d\\d)(?:\\.\\d+)?Z$/D';
        if (preg_match($regex, $time, $matches) == 0) {
            throw new Exception("Invalid SAML2 timestamp passed to xsDateTimeToTimestamp: " . $time);
        }

        // Extract the different components of the time from the  matches in the regex.
        // intval will ignore leading zeroes in the string.
        $year = intval($matches[1]);
        $month = intval($matches[2]);
        $day = intval($matches[3]);
        $hour = intval($matches[4]);
        $minute = intval($matches[5]);
        $second = intval($matches[6]);

        // We use gmmktime because the timestamp will always be given
        //in UTC.
        $ts = gmmktime($hour, $minute, $second, $month, $day, $year);

        return $ts;
    }

    /**
     * @param DOMElement $encryptedData
     * @param XMLSecurityKey $inputKey
     * @param array $blacklist
     * @param XMLSecurityKey|NULL $alternateKey
     * @return DOMElement
     * @throws Exception
     */
    public static function decryptElement(DOMElement $encryptedData, XMLSecurityKey $inputKey,
                                          array      $blacklist = array(), XMLSecurityKey $alternateKey = NULL)
    {
        try {
            return self::doDecryptElement($encryptedData, $inputKey, $blacklist);
        } catch (Exception $e) {
            //Try with alternate key
            try {
                return self::doDecryptElement($encryptedData, $alternateKey, $blacklist);
            } catch (Exception $t) {
                throw new Exception('Failed to decrypt XML element.');
            }
            /*
             * Something went wrong during decryption, but for security
             * reasons we cannot tell the user what failed.
             */
            throw new Exception('Failed to decrypt XML element.');
        }
    }

    /**
     * Decrypt an encrypted element.
     *
     * This is an internal helper function.
     *
     * @param DOMElement $encryptedData The encrypted data.
     * @param XMLSecurityKey $inputKey The decryption key.
     * @param array          &$blacklist Blacklisted decryption algorithms.
     * @return DOMElement     The decrypted element.
     * @throws Exception
     */
    private static function doDecryptElement(DOMElement $encryptedData, XMLSecurityKey $inputKey, array &$blacklist)
    {
        $enc = new XMLSecEnc();
        $enc->setNode($encryptedData);

        $enc->type = $encryptedData->getAttribute("Type");
        $symmetricKey = $enc->locateKey($encryptedData);
        if (!$symmetricKey) {
            throw new Exception('Could not locate key algorithm in encrypted data.');
        }

        $symmetricKeyInfo = $enc->locateKeyInfo($symmetricKey);
        if (!$symmetricKeyInfo) {
            throw new Exception('Could not locate <dsig:KeyInfo> for the encrypted key.');
        }
        $inputKeyAlgo = $inputKey->getAlgorith();
        if ($symmetricKeyInfo->isEncrypted) {
            $symKeyInfoAlgo = $symmetricKeyInfo->getAlgorith();
            if (in_array($symKeyInfoAlgo, $blacklist, TRUE)) {
                throw new Exception('Algorithm disabled: ' . var_export($symKeyInfoAlgo, TRUE));
            }
            if ($symKeyInfoAlgo === XMLSecurityKey::RSA_OAEP_MGF1P && $inputKeyAlgo === XMLSecurityKey::RSA_1_5) {
                /*
                 * The RSA key formats are equal, so loading an RSA_1_5 key
                 * into an RSA_OAEP_MGF1P key can be done without problems.
                 * We therefore pretend that the input key is an
                 * RSA_OAEP_MGF1P key.
                 */
                $inputKeyAlgo = XMLSecurityKey::RSA_OAEP_MGF1P;
            }
            /* Make sure that the input key format is the same as the one used to encrypt the key. */
            if ($inputKeyAlgo !== $symKeyInfoAlgo) {
                throw new Exception('Algorithm mismatch between input key and key used to encrypt ' .
                    ' the symmetric key for the message. Key was: ' .
                    var_export($inputKeyAlgo, TRUE) . '; message was: ' .
                    var_export($symKeyInfoAlgo, TRUE));
            }
            /** @var XMLSecEnc $encKey */
            $encKey = $symmetricKeyInfo->encryptedCtx;
            $symmetricKeyInfo->key = $inputKey->key;
            $keySize = $symmetricKey->getSymmetricKeySize();
            if ($keySize === NULL) {
                /* To protect against "key oracle" attacks, we need to be able to create a
                 * symmetric key, and for that we need to know the key size.
                 */
                throw new Exception('Unknown key size for encryption algorithm: ' . var_export($symmetricKey->type, TRUE));
            }
            try {
                $key = $encKey->decryptKey($symmetricKeyInfo);
                if (strlen($key) != $keySize) {
                    throw new Exception('Unexpected key size (' . strlen($key) * 8 . 'bits) for encryption algorithm: ' .
                        var_export($symmetricKey->type, TRUE));
                }
            } catch (Exception $e) {
                /* We failed to decrypt this key. Log it, and substitute a "random" key. */

                /* Create a replacement key, so that it looks like we fail in the same way as if the key was correctly padded. */
                /* We base the symmetric key on the encrypted key and private key, so that we always behave the
                 * same way for a given input key.
                 */
                $encryptedKey = $encKey->getCipherValue();
                $pkey = openssl_pkey_get_details($symmetricKeyInfo->key);
                $pkey = sha1(serialize($pkey), TRUE);
                $key = sha1($encryptedKey . $pkey, TRUE);
                /* Make sure that the key has the correct length. */
                if (strlen($key) > $keySize) {
                    $key = substr($key, 0, $keySize);
                } elseif (strlen($key) < $keySize) {
                    $key = str_pad($key, $keySize);
                }
            }
            $symmetricKey->loadkey($key);
        } else {
            $symKeyAlgo = $symmetricKey->getAlgorith();
            /* Make sure that the input key has the correct format. */
            if ($inputKeyAlgo !== $symKeyAlgo) {
                throw new Exception('Algorithm mismatch between input key and key in message. ' .
                    'Key was: ' . var_export($inputKeyAlgo, TRUE) . '; message was: ' .
                    var_export($symKeyAlgo, TRUE));
            }
            $symmetricKey = $inputKey;
        }
        $algorithm = $symmetricKey->getAlgorith();
        if (in_array($algorithm, $blacklist, TRUE)) {
            throw new Exception('Algorithm disabled: ' . var_export($algorithm, TRUE));
        }
        /** @var string $decrypted */
        $decrypted = $enc->decryptNode($symmetricKey, FALSE);
        /*
         * This is a workaround for the case where only a subset of the XML
         * tree was serialized for encryption. In that case, we may miss the
         * namespaces needed to parse the XML.
         */
        $xml = '<root xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ' .
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
            $decrypted .
            '</root>';
        $newDoc = new DOMDocument();
        if (!@$newDoc->loadXML($xml)) {
            throw new Exception('Failed to parse decrypted XML. Maybe the wrong sharedkey was used?');
        }

        $decryptedElement = $newDoc->firstChild->firstChild;
        if ($decryptedElement === NULL) {
            throw new Exception('Missing encrypted element.');
        }

        if (!($decryptedElement instanceof DOMElement)) {
            throw new Exception('Decrypted element was not actually a DOMElement.');
        }

        return $decryptedElement;
    }

    public static function extractStrings(DOMElement $parent, $namespaceURI, $localName)
    {
        $ret = array();
        for ($node = $parent->firstChild; $node !== NULL; $node = $node->nextSibling) {
            if ($node->namespaceURI !== $namespaceURI || $node->localName !== $localName) {
                continue;
            }
            $ret[] = trim($node->textContent);
        }

        return $ret;
    }

    /**
     * @param DOMElement $root
     * @return array|bool
     * @throws Exception
     */
    public static function validateElement(DOMElement $root)
    {
        /* Create an XML security object. */
        $objXMLSecDSig = new XMLSecurityDSig();

        /* Both SAML messages and SAML assertions use the 'ID' attribute. */
        $objXMLSecDSig->idKeys[] = 'ID';


        /* Locate the XMLDSig Signature element to be used. */
        $signatureElement = self::xpQuery($root, './ds:Signature');

        if (count($signatureElement) === 0) {
            /* We don't have a signature element to validate. */
            return FALSE;
        } elseif (count($signatureElement) > 1) {
            throw new Exception("XMLSec: more than one signature element in root.");
        }

        $signatureElement = $signatureElement[0];
        $objXMLSecDSig->sigNode = $signatureElement;

        /* Canonicalize the XMLDSig SignedInfo element in the message. */
        $objXMLSecDSig->canonicalizeSignedInfo();

        /* Validate referenced xml nodes. */
        if (!$objXMLSecDSig->validateReference()) {
            throw new Exception("XMLsec: digest validation failed");
        }

        /* Check that $root is one of the signed nodes. */
        $rootSigned = FALSE;
        /** @var DomNode $signedNode */
        foreach ($objXMLSecDSig->getValidatedNodes() as $signedNode) {
            if ($signedNode->isSameNode($root)) {
                $rootSigned = TRUE;
                break;
            } elseif ($root->parentNode instanceof DOMDocument && $signedNode->isSameNode($root->ownerDocument)) {
                /* $root is the root element of a signed document. */
                $rootSigned = TRUE;
                break;
            }
        }

        if (!$rootSigned) {
            throw new Exception("XMLSec: The root element is not signed.");
        }

        /* Now we extract all available X509 certificates in the signature element. */
        $certificates = array();
        foreach (self::xpQuery($signatureElement, './ds:KeyInfo/ds:X509Data/ds:X509Certificate') as $certNode) {
            $certData = trim($certNode->textContent);
            $certData = str_replace(array("\r", "\n", "\t", ' '), '', $certData);
            $certificates[] = $certData;
        }

        $ret = array(
            'Signature' => $objXMLSecDSig,
            'Certificates' => $certificates,
        );


        return $ret;
    }

    public static function xpQuery(DomNode $node, $query)
    {
        static $xpCache = NULL;

        if ($node instanceof DOMDocument) {
            $doc = $node;
        } else {
            $doc = $node->ownerDocument;
        }

        if ($xpCache === NULL || !$xpCache->document->isSameNode($doc)) {
            $xpCache = new \DOMXPath($doc);
            $xpCache->registerNamespace('soap-env', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xpCache->registerNamespace('saml_protocol', 'urn:oasis:names:tc:SAML:2.0:protocol');
            $xpCache->registerNamespace('saml_assertion', 'urn:oasis:names:tc:SAML:2.0:assertion');
            $xpCache->registerNamespace('saml_metadata', 'urn:oasis:names:tc:SAML:2.0:metadata');
            $xpCache->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
            $xpCache->registerNamespace('xenc', 'http://www.w3.org/2001/04/xmlenc#');
        }

        $results = $xpCache->query($query, $node);
        $ret = array();
        for ($i = 0; $i < $results->length; $i++) {
            $ret[$i] = $results->item($i);
        }

        return $ret;
    }

    /**
     * @param $certFingerprint
     * @param $signatureData
     * @return bool
     * @throws Exception
     */
    public static function processResponse($certFingerprint, $signatureData)
    {
        $responseSigned = self::checkSign($certFingerprint, $signatureData);
        return $responseSigned;
    }

    /**
     * @param $certFingerprint
     * @param $signatureData
     * @return bool
     * @throws Exception
     */
    public static function checkSign($certFingerprint, $signatureData)
    {
        $certificates = $signatureData['Certificates'];

        if (count($certificates) === 0) {
            return FALSE;
        }

        $fpArray = array();
        $fpArray[] = $certFingerprint;
        $pemCert = self::findCertificate($fpArray, $certificates);
        if ($pemCert === FALSE) return FALSE;
        $lastException = NULL;

        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, array('type' => 'public'));
        $key->loadKey($pemCert);

        try {
            /*
             * Make sure that we have a valid signature
             */
            self::validateSignature($signatureData, $key);
            return TRUE;
        } catch (Exception $e) {
            $lastException = $e;
        }


        /* We were unable to validate the signature with any of our keys. */
        if ($lastException !== NULL) {
            throw $lastException;
        } else {
            return FALSE;
        }

    }

    private static function findCertificate(array $certFingerprints, array $certificates)
    {
        $candidates = array();

        foreach ($certificates as $cert) {
            $fp = strtolower(sha1(base64_decode($cert)));
            if (!in_array($fp, $certFingerprints, TRUE)) {
                $candidates[] = $fp;
                continue;
            }

            /* We have found a matching fingerprint. */
            $pem = "-----BEGIN CERTIFICATE-----\n" .
                chunk_split($cert, 64) .
                "-----END CERTIFICATE-----\n";

            return $pem;
        }

        return FALSE;
    }

    /**
     * @param array $info
     * @param XMLSecurityKey $key
     * @throws Exception
     */
    public static function validateSignature(array $info, XMLSecurityKey $key)
    {
        /** @var XMLSecurityDSig $objXMLSecDSig */
        $objXMLSecDSig = $info['Signature'];

        $sigMethod = self::xpQuery($objXMLSecDSig->sigNode, './ds:SignedInfo/ds:SignatureMethod');
        if (empty($sigMethod)) {
            throw new Exception('Missing SignatureMethod element');
        }
        $sigMethod = $sigMethod[0];
        if (!$sigMethod->hasAttribute('Algorithm')) {
            throw new Exception('Missing Algorithm-attribute on SignatureMethod element.');
        }
        $algo = $sigMethod->getAttribute('Algorithm');

        if ($key->type === XMLSecurityKey::RSA_SHA256 && $algo !== $key->type) {
            $key = self::castKey($key, $algo);
        }

        /* Check the signature. */
        if (!$objXMLSecDSig->verify($key)) {
            throw new Exception('Unable to validate Sgnature');
        }
    }

    /**
     * @param XMLSecurityKey $key
     * @param $algorithm
     * @param string $type
     * @return XMLSecurityKey
     * @throws Exception
     */
    public static function castKey(XMLSecurityKey $key, $algorithm, $type = 'public')
    {
        // do nothing if algorithm is already the type of the key
        if ($key->type === $algorithm) {
            return $key;
        }

        $keyInfo = openssl_pkey_get_details($key->key);
        if ($keyInfo === FALSE) {
            throw new Exception('Unable to get key details from XMLSecurityKey.');
        }
        if (!isset($keyInfo['key'])) {
            throw new Exception('Missing key in public key details.');
        }

        $newKey = new XMLSecurityKey($algorithm, array('type' => $type));
        $newKey->loadKey($keyInfo['key']);

        return $newKey;
    }

    public static function getEncryptionAlgorithm($method)
    {
        switch ($method) {
            case 'http://www.w3.org/2001/04/xmlenc#tripledes-cbc':
                return XMLSecurityKey::TRIPLEDES_CBC;

            case 'http://www.w3.org/2001/04/xmlenc#aes128-cbc':
                return XMLSecurityKey::AES128_CBC;

            case 'http://www.w3.org/2001/04/xmlenc#aes192-cbc':
                return XMLSecurityKey::AES192_CBC;

            case 'http://www.w3.org/2001/04/xmlenc#aes256-cbc':
                return XMLSecurityKey::AES256_CBC;

            case 'http://www.w3.org/2001/04/xmlenc#rsa-1_5':
                return XMLSecurityKey::RSA_1_5;

            case 'http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p':
                return XMLSecurityKey::RSA_OAEP_MGF1P;

            case 'http://www.w3.org/2000/09/xmldsig#dsa-sha1':
                return XMLSecurityKey::DSA_SHA1;

            case 'http://www.w3.org/2000/09/xmldsig#rsa-sha1':
                return XMLSecurityKey::RSA_SHA1;

            case 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256':
                return XMLSecurityKey::RSA_SHA256;

            case 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha384':
                return XMLSecurityKey::RSA_SHA384;

            case 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512':
                return XMLSecurityKey::RSA_SHA512;

            default:
                throw new Exception('Invalid Encryption Method: ' . $method);
        }
    }

    public static function sanitize_certificate($certificate)
    {
        $certificate = preg_replace("/[\r\n]+/", "", $certificate);
        $certificate = str_replace("-", "", $certificate);
        $certificate = str_replace("BEGIN CERTIFICATE", "", $certificate);
        $certificate = str_replace("END CERTIFICATE", "", $certificate);
        $certificate = str_replace(" ", "", $certificate);
        $certificate = chunk_split($certificate, 64, "\r\n");
        $certificate = "-----BEGIN CERTIFICATE-----\r\n" . $certificate . "-----END CERTIFICATE-----";
        return $certificate;
    }

    public static function desanitize_certificate($certificate)
    {
        $certificate = preg_replace("/[\r\n]+/", "", $certificate);
        $certificate = str_replace("-----BEGIN CERTIFICATE-----", "", $certificate);
        $certificate = str_replace("-----END CERTIFICATE-----", "", $certificate);
        $certificate = str_replace(" ", "", $certificate);
        return $certificate;
    }

    public static function generateRandomAlphanumericValue($length)
    {
        $chars = "abcdef0123456789";
        $chars_len = strlen($chars);
        $uniqueID = "";
        for ($i = 0; $i < $length; $i++)
            $uniqueID .= substr($chars, rand(0, 15), 1);
        return 'a' . $uniqueID;
    }

    public static function mo_saml_miniorange_generate_metadata($download = false)
    {
        $spObject = json_decode(self::fetchFromTable(Constants::SAML_SPOBJECT, Constants::TABLE_SAML), true);
        $spObject = $spObject[Constants::SAML_SPOBJECT];
        $sp_base_url = $spObject['site_base_url'];
        $sp_response_url = $spObject['response'];
        $sp_entity_id = $spObject['sp_entity_id'];

        $entity_id = $sp_entity_id;
        $acs_url = $sp_response_url;
        if (ob_get_contents())
            ob_clean();

        header('Content-Type: text/rss+xml; charset=utf-8');
        if ($download)
            header('Content-Disposition: attachment; filename="Metadata.xml"');

        echo '<?xml version="1.0"?><md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" validUntil="2022-10-28T23:59:59Z" cacheDuration="PT1446808792S" entityID="' . $entity_id . '">
                <md:SPSSODescriptor AuthnRequestsSigned="false" WantAssertionsSigned="true" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
                <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</md:NameIDFormat>
                <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="' . $acs_url . '" index="1"/>
                </md:SPSSODescriptor>
                <md:Organization>
                <md:OrganizationName xml:lang="en-US">miniOrange</md:OrganizationName>
                <md:OrganizationDisplayName xml:lang="en-US">miniOrange</md:OrganizationDisplayName>
                <md:OrganizationURL xml:lang="en-US">http://miniorange.com</md:OrganizationURL>
                </md:Organization>
                <md:ContactPerson contactType="technical">
                <md:GivenName>miniOrange</md:GivenName>
                <md:EmailAddress>info@xecurify.com</md:EmailAddress>
                </md:ContactPerson>
                <md:ContactPerson contactType="support">
                <md:GivenName>miniOrange</md:GivenName> 
                <md:EmailAddress>info@xecurify.com</md:EmailAddress>
                </md:ContactPerson>
                </md:EntityDescriptor>';
        exit;

    }

    public static function download_sp_certificate()
    {
        if (ob_get_contents())
            ob_clean();
        header('Content-Disposition: attachment; filename="sp-cert.crt"');

        echo '-----BEGIN CERTIFICATE-----
            MIIDoTCCAokCFAkYOm9dmoCuy2scWoVezjHAIYInMA0GCSqGSIb3DQEBCwUAMIGM
            MQswCQYDVQQGEwJJTjELMAkGA1UECAwCTUgxDTALBgNVBAcMBFBVTkUxEzARBgNV
            BAoMCk1JTklPUkFOR0UxEzARBgNVBAsMCk1JTklPUkFOR0UxEzARBgNVBAMMCk1J
            TklPUkFOR0UxIjAgBgkqhkiG9w0BCQEWE2luZm9AbWluaW9yYW5nZS5jb20wHhcN
            MjIwMjIxMTExNjAyWhcNMjcwMjIwMTExNjAyWjCBjDELMAkGA1UEBhMCSU4xCzAJ
            BgNVBAgMAk1IMQ0wCwYDVQQHDARQVU5FMRMwEQYDVQQKDApNSU5JT1JBTkdFMRMw
            EQYDVQQLDApNSU5JT1JBTkdFMRMwEQYDVQQDDApNSU5JT1JBTkdFMSIwIAYJKoZI
            hvcNAQkBFhNpbmZvQG1pbmlvcmFuZ2UuY29tMIIBIjANBgkqhkiG9w0BAQEFAAOC
            AQ8AMIIBCgKCAQEA4Dp+Gm15FArPhyGw44CMOXvHuXOsU4RNGm5rpB4LRZh8eGOQ
            aJPBck3s8oM0lOE2jugxuBtPBFlM0LT4hdWo/wfSPFvbcXvKegz9DXPGd7pCfJ34
            N++g259sKTB8/EtwpfqCd06SPIQ/+LpPgihULQfGOpHz/hB7vCZO0uHO0pZw2gvt
            mA1uJ5r9EFm+t6gAo0H87t3D/0Re8YLsu/mJTasEaAAadA39DdrK5LjBHR7ua716
            u7p69Wh84QAV6G78ySGeovzubtq0+wdFdM2P/qVrecI/OgMZUee1HtJ4QSNPESPY
            2vIsMi1qA4oJOHK3dmnM9rqDqDI3KSo4syvr4QIDAQABMA0GCSqGSIb3DQEBCwUA
            A4IBAQBVRch/h7SrDn8rmdZmf91hNoE5fn0R4AcYUhpKcJw9Jx3Dy9pJFczao3e0
            ni4vx59dyVbTR4pRyvsm1XvT165vGW1McYbNwA0yLbpJkM2TdrS4ydb0RukoCyJy
            RxRya+dWZgrmCKlYMaAJmXsD5P3EvGHdeBIYBwHeZSaq2LEvq8BdBoK9nHkpT6Si
            I/zgrEiy3Cy2GZO9L9NEGydb9f8cqWzQKYIH5Yh7SMb5fP6QE26MiKxaVATw26e8
            WWMBwGCbafSjBa2H5Zaj//uHlQFmgbopCe6m2lIq0YGkWNpPt4jUbswGolVF/408
            hntu8CcHMp+Rx5pL7aLdGfKri6Ly
            -----END CERTIFICATE-----';
        exit;
    }
}
