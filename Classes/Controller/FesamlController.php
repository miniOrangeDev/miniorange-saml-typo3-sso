<?php
namespace MiniOrange\MiniorangeSaml\Controller;

use MiniOrange\Helper\Constants;
use MiniOrange\Helper\Messages;
use MiniOrange\Helper\PluginSettings;
use MiniOrange\SSO;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use MiniOrange\Helper\SAMLUtilities;
use TYPO3\CMS\Core\Database\ConnectionPool;
use MiniOrange\Classes\Actions;
use MiniOrange\Classes;
use MiniOrange\Classes\SamlResponse;
use MiniOrange\Helper;
use TYPO3\CMS\Core\Cache\CacheManager;
use MiniOrange\Helper\Lib\XMLSecLibs\XMLSecurityKey;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController;
/***
 *
 * This file is part of the "miniorangesaml" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 miniOrange <info@miniorange.com>, miniorange
 *
 ***/

/**
 * FesamlController
 */
class FesamlController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * fesamlRepository
     * 
     * @var \MiniOrange\MiniorangeSaml\Domain\Repository\FesamlRepository
     * @inject
     */
    protected $fesamlRepository = null;

    protected $idp_name = null;

    protected $acs_url = null;

    protected $sp_entity_id = null;

    protected $force_authn = null;

    protected $saml_login_url = null;

    private $issuer = null;

    private $ssoUrl = null;

    private $signedAssertion = null;

    private $signedResponse = null;

    protected $x509_certificate = null;

    protected $uid = 1;

    /**
     * action list
     * 
     * @return void
     */
    public function listAction()
    {
        $fesamls = $this->fesamlRepository->findAll();
        $this->view->assign('fesamls', $fesamls);
    }

    /**
     * action show
     * 
     * @param \MiniOrange\MiniorangeSaml\Domain\Model\Fesaml $fesaml
     * @return void
     */
    public function showAction(\MiniOrange\MiniorangeSaml\Domain\Model\Fesaml $fesaml)
    {
        $this->view->assign('fesaml', $fesaml);
    }

    /**
     * action print
     * 
     * @return void
     */
    public function printAction()
    {
        //$this->flushCaches ();
        $this->cacheService->clearPageCache([$GLOBALS['TSFE']->id]);
        $caches = new TypoScriptTemplateModuleController();
        $caches->clearCache();
        $this->controlAction();
        $samlRequest = $this->build();
        $relayState = isset($_REQUEST['RelayState']) ? $_REQUEST['RelayState'] : '/';
        if ($this->findSubstring($_REQUEST) == 1) {
            $relayState = 'testconfig';
        }
        if (empty($this->bindingType) || $this->bindingType == Constants::HTTP_REDIRECT) {
            $this->sendHTTPRedirectRequest($samlRequest, $relayState, $this->saml_login_url);
        } else {
            $this->sendHTTPPostRequest($samlRequest, $relayState, $this->saml_login_url);
        }

    }

    /**
     * action control
     * 
     * @return void
     */
    public function controlAction()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $this->idp_name = $queryBuilder->select('idp_name')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->acs_url = $queryBuilder->select('acs_url')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->sp_entity_id = $queryBuilder->select('sp_entity_id')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->saml_login_url = $queryBuilder->select('saml_login_url')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->force_authn = $queryBuilder->select('force_authn')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->x509_certificate = $queryBuilder->select('x509_certificate')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->issuer = $queryBuilder->select('idp_entity_id')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $signedAssertion = true;
        $signedResponse = true;
    }
    public function findSubstring($request)
    {
        if (strpos($request["id"], 'RelayState') !== false) {
            return 1;
        } else {
            return 0;
        }
    }
    public function build()
    {
        //$pluginSettings=PluginSettings::getPluginSettings();
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
     * @param $sloUrl
     */
    /**
     * @param $samlRequest
     * @param $sendRelayState
     * @param $idpUrl
     */
    public function sendHTTPRedirectRequest($samlRequest, $sendRelayState, $idpUrl)
    {
        $samlRequest = 'SAMLRequest=' . $samlRequest . '&RelayState=' . urlencode($sendRelayState) . '&SigAlg=' . urlencode(XMLSecurityKey::RSA_SHA256);
        $param = ['type' => 'private'];
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, $param);
        $certFilePath = file_get_contents(__DIR__ . '/../../sso/resources/sp-key.key');
        $key->loadKey($certFilePath);
        $signature = $key->signData($samlRequest);
        $signature = base64_encode($signature);
        $redirect = $idpUrl;
        $redirect .= strpos($idpUrl, '?') !== false ? '&' : '?';
        $redirect .= $samlRequest . '&Signature=' . urlencode($signature);
        //var_dump
        //($redirect);exit;
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
                                <saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">' . $this->issuer . '</saml:Issuer>
                                <samlp:NameIDPolicy AllowCreate="true" Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified"/>
                            </samlp:AuthnRequest>';
        return $requestXmlStr;
    }

    /**
     * @param $instant
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
     */
    public function generateRandomBytes($length, $fallback = TRUE)
    {
        return openssl_random_pseudo_bytes($length);
    }
}
