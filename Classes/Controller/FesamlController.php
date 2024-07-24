<?php
namespace Miniorange\Sp\Controller;

use Miniorange\Sp\Helper\Actions\ProcessResponseAction;
use Miniorange\Sp\Helper\Actions\ReadResponseAction;
use Miniorange\Sp\Helper\Actions\TestResultActions;
use Miniorange\Sp\Helper\Constants;
use Miniorange\Sp\Helper\Messages;
use Miniorange\Sp\Helper\PluginSettings;
use Miniorange\Sp\Domain\Model\Fesaml;
use Miniorange\SSO;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Miniorange\Sp\Helper\SAMLUtilities;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController;
use Miniorange\Sp\Helper\Actions;
use Miniorange\Classes;
use Miniorange\Sp\Helper\SamlResponse;
use Miniorange\Sp\Helper;
use Miniorange\Sp\Helper\Lib\XMLSecLibs\XMLSecurityKey;
use Miniorange\Sp\Helper\Utilities;
use TYPO3\CMS\Core\Database\Connection;
use PDO;
use TYPO3\CMS\Core\Information\Typo3Version;
/***
 *
 * This file is part of the "miniOrange SAML" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Miniorange <info@xecurify.com>
 *
 ***/

/**
 * FesamlController
 */
class FesamlController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    protected $idp_name = null;

    protected $acs_url = null;

    protected $sp_entity_id = null;

    protected $force_authn = null;

    protected $saml_login_url = null;

    private $idp_entity_id = null;

    private $ssoUrl = null;

    private $destination = null;

    private $bindingType = null;

    private $signedAssertion = null;

    private $signedResponse = null;

    protected $x509_certificate = null;

    protected $uid = 1;

    /**
     * action list
     * 
     * @param Miniorange\Sp\Domain\Model\Fesaml
     * @param Miniorange\Sp\Domain\Model\Fesaml
     * @return void
     * @return void
     */
    public function listAction()
    {
        $samlmodels = $this->samlmodelRepository->findAll();
        $this->view->assign('samlmodels', $samlmodels);
    }

    /**
     * action print
     * @return void
     * @throws \Exception
     */
    public function requestAction()
    {
        $version = new Typo3Version();
        $typo3Version = $version->getVersion();
        if(isset($_REQUEST['option']) and $_REQUEST['option']=='mosaml_metadata')
        {
            SAMLUtilities::mo_saml_miniorange_generate_metadata();
        }
        error_log("relaystate :  ".print_r($_REQUEST,true));
        $this->controlAction();
        $this->bindingType = $this->fetchBindingType();
        $samlRequest = $this->build();
        $relayState = isset($_REQUEST['RelayState']) ? $_REQUEST['RelayState'] : '/';
        
        if ($this->findSubstring($_REQUEST) == 1) {
            $relayState = 'testconfig';
        }

        $this->controlAction();

        error_log("relaystate :  ".$relayState);

        if(!isset($this->acs_url) || !isset($this->sp_entity_id) || !isset($this->idp_entity_id) || !isset($this->saml_login_url) || !isset($this->x509_certificate))
        {
            Utilities::showErrorFlashMessage('Please make fill all the necessary fields.');
            return;
        }
        $this->sendHTTPRedirectRequest($samlRequest, $relayState, $this->saml_login_url);
        GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->flushCaches();


        if ($typo3Version >= 11.5) {
            return $this->responseFactory->createResponse()
                ->withAddedHeader('Content-Type', 'text/html; charset=utf-8')
                ->withBody($this->streamFactory->createStream($this->view->render()));
        }    
    }

    /**
     * @param $request
     * @return int
     */
    public function findSubstring($request)
    {
         if(!empty($request["id"]))
         { 
            if (strpos($request["id"], 'RelayState') !== false) 
            {
                return 1;
            } else 
            {
                return 0;
            }
        }
        return 0;
    }

    //Function to fetch login binding type
    public function fetchBindingType()
    {
        $this->bindingType = Utilities::fetchFromTable(Constants::COLUMN_IDP_LOGIN_BINDING_TYPE, Constants::TABLE_SAML);
    }

     /**
     * action control
     * 
     * @return void
     */
    public function controlAction()
    {
        $idp_object = json_decode(Utilities::fetchFromTable(Constants::COLUMN_OBJECT_IDP, Constants::TABLE_SAML),true);
        $sp_object = json_decode(Utilities::fetchFromTable(Constants::COLUMN_OBJECT_SP,Constants::TABLE_SAML),true);

         $this->idp_name = $idp_object[Constants::COLUMN_IDP_NAME];
         $this->idp_entity_id =$idp_object[Constants::COLUMN_IDP_ENTITY_ID];
         $this->saml_login_url = $idp_object[Constants::COLUMN_IDP_LOGIN_URL];
         $this->x509_certificate = $idp_object[Constants::COLUMN_IDP_CERTIFICATE];
         $this->force_authn = false;

        $this->acs_url = $sp_object[Constants::COLUMN_SP_ACS_URL];
        $this->sp_entity_id = $sp_object[Constants::COLUMN_SP_ENTITY_ID];

        $this->signedAssertion = true;
        $this->signedResponse = true;

        $this->destination = $this->saml_login_url;
    }

    public function build()
    {
        $requestXmlStr = $this->generateXML();
        if (empty($this->bindingType) || $this->bindingType == Constants::HTTP_REDIRECT) {
            $deflatedStr = gzdeflate($requestXmlStr);
            $base64EncodedStr = base64_encode($deflatedStr);
            $urlEncoded = urlencode($base64EncodedStr);
            $requestXmlStr = $urlEncoded;
        }
        return $requestXmlStr;
    }

    /**
     * @param $samlRequest
     * @param $sendRelayState
     * @param $idpUrl
     * @throws Exception
     */
    public function sendHTTPRedirectRequest($samlRequest, $sendRelayState, $idpUrl)
    {
        $samlRequest = 'SAMLRequest=' . $samlRequest . '&RelayState=' . urlencode($sendRelayState) . '&SigAlg=' . urlencode(XMLSecurityKey::RSA_SHA256);
        $param = ['type' => 'private'];
        $redirect = $idpUrl;
        $redirect .= strpos($idpUrl, '?') !== false ? '&' : '?';
        $redirect .= $samlRequest ;
        if (isset($_REQUEST)) {
            header('Location:' . $redirect);
            die;
        }
    }

    private function generateXML()
    {
        $requestXmlStr = '<?xml version="1.0" encoding="UTF-8"?>' . ' <samlp:AuthnRequest 
                                xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" 
                                xmlns="urn:oasis:names:tc:SAML:2.0:assertion" ID="' . $this->generateID() . '"  Version="2.0" IssueInstant="' . $this->generateTimestamp() . '"';
        // add force authn element
        if ($this->force_authn) {
            $requestXmlStr .= ' ForceAuthn="true"';
        }
        $requestXmlStr .= '     ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" AssertionConsumerServiceURL="' . $this->acs_url . '"      Destination="' . $this->destination . '">
                                <saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">' . $this->sp_entity_id . '</saml:Issuer>
                                <samlp:NameIDPolicy AllowCreate="true" Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified"/>
                            </samlp:AuthnRequest>';
        return $requestXmlStr;
    }

    /**
     * @param $instant
     * @return false|string
     */
    public function generateTimestamp($instant = NULL)
    {
        if ($instant === NULL) {
            $instant = time();
        }
        return gmdate('Y-m-d\\TH:i:s\\Z', $instant);
    }

    public function generateID()
    {
        $str = $this->stringToHex($this->generateRandomBytes(21));
        return '_' . $str;
    }

    /**
     * @param $bytes
     * @return string
     */
    public static function stringToHex($bytes)
    {
        $ret = '';
        for ($i = 0; $i < strlen($bytes); $i++) {
            $ret .= sprintf('%02x', ord($bytes[$i]));
        }
        return $ret;
    }

    /**
     * @param $length
     * @param $fallback
     * @return string
     */
    public function generateRandomBytes($length, $fallback = TRUE)
    {
        return openssl_random_pseudo_bytes($length);
    }
}

