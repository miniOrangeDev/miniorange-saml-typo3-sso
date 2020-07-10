<?php
namespace Miniorange\MiniorangeSaml\Controller;


use Miniorange\Helper\Constants;
use Miniorange\Helper\SAMLUtilities;
use Miniorange\Helper\SamlResponse;
use Miniorange\Helper\Utilities;
use Miniorange\MiniorangeSaml\Domain\Model\Fesaml;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Miniorange\Helper\lib\XMLSecLibs\XMLSecurityKey;

/**
 * FesamlController
 */
class FesamlController extends ActionController
{
//    /**
//     * fesamlRepository
//     *
//     * @var \Miniorange\MiniorangeSaml\Domain\Repository\FesamlRepository
//     * @inject
//     */
//    protected $fesamlRepository = null;

    protected $idp_name = null;

    protected $acs_url = null;

    protected $sp_entity_id = null;

    protected $force_authn = null;

    protected $saml_login_url = null;

    protected $destination = null;

    private $idp_entity_id = null;

    private $ssoUrl = null;

    private $bindingType = null;

    private $signedAssertion = null;

    private $signedResponse = null;

    protected $x509_certificate = null;

    protected $uid = 1;

//    /**
//     * action list
//     *
//     * @param Miniorange\MiniorangeSaml\Domain\Model\Fesaml
//     * @return void
//     */
//    public function listAction()
//    {
//        $samlmodels = $this->samlmodelRepository->findAll();
//        $this->view->assign('samlmodels', $samlmodels);
//    }

//    /**
//     * action show
//     *
//     * @param Fesaml $fesaml
//     * @return void
//     */
//    public function showAction(Fesaml $fesaml)
//    {
//        $samlmodels = $this->samlmodelRepository->findAll();
//        $this->view->assign('samlmodel', $samlmodels);
//    }

    /**
     * action print
     *
     * @param Miniorange\MiniorangeSaml\Domain\Model\Fesaml
     * @return void
     * @throws \Exception
     */
    public function requestAction()
    {
        $this->cacheService->clearPageCache([$GLOBALS['TSFE']->id]);

        $this->controlAction();

        $this->bindingType = $this->fetchBindingType();
        error_log("FesamlController : binding type : ".$this->bindingType);

        $samlRequest = $this->build();

        $relayState = isset($_REQUEST['RelayState']) ? $_REQUEST['RelayState'] : '/';
        error_log("relaystate :  ".$relayState);

        if ($this->findSubstring($_REQUEST) == 1) {
            $relayState = 'testconfig';
        }

        if (empty($this->bindingType) || ($this->bindingType == Constants::HTTP_REDIRECT)) {
            $this->sendHTTPRedirectRequest($samlRequest, $relayState, $this->saml_login_url);
        } else {
            $this->sendHTTPPostRequest($samlRequest, $relayState, $this->saml_login_url);
        }

    }

    /**
     * @param $request
     * @return int
     */
    public function findSubstring($request)
    {
        if (strpos($request["id"], 'RelayState') !== false) {
            return 1;
        }else{
            return 0;
        }
    }

    public function fetchBindingType(){
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $this->bindingType = $queryBuilder->select('login_binding_type')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        return $this->bindingType;
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
        $this->idp_entity_id = $queryBuilder->select('idp_entity_id')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->signedAssertion = true;
        $this->signedResponse = true;
        $this->destination = $this->saml_login_url;
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
    public function sendHTTPPostRequest($samlRequest, $sendRelayState, $sloUrl){
        $privateKeyPath = file_get_contents(__DIR__ . '/../../sso/resources/sp-key.key');
        $publicCertPath = file_get_contents(__DIR__ . '/../../sso/resources/sp-certificate.crt');
        $signedXML = SAMLUtilities::signXML($samlRequest, $publicCertPath, $privateKeyPath, 'NameIDPolicy');
        $base64EncodedXML = base64_encode($signedXML);
        //post request
        ob_clean();
        printf('  <html><head><script src=\'https://code.jquery.com/jquery-1.11.3.min.js\'></script><script type="text/javascript">
                    $(function(){document.forms[\'saml-request-form\'].submit();});</script></head>
                    <body>
                        Please wait...
                        <form action="%s" method="post" id="saml-request-form" style="display:none;">
                            <input type="hidden" name="SAMLRequest" value="%s" />
                            <input type="hidden" name="RelayState" value="%s" />
                        </form>
                    </body>
                </html>',
            $sloUrl, $base64EncodedXML, htmlentities($sendRelayState)
        );
    }

    /**
     * @param $samlRequest
     * @param $sendRelayState
     * @param $idpUrl
     * @throws \Exception
     */
    public function sendHTTPRedirectRequest($samlRequest, $sendRelayState, $idpUrl)
    {
        $samlRequest = 'SAMLRequest=' . $samlRequest . '&RelayState=' . urlencode($sendRelayState) . '&SigAlg=' . urlencode(XMLSecurityKey::RSA_SHA256);
        $param = ['type' => 'private'];
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, $param);
        $certFilePath = file_get_contents(Utilities::getBaseUrl().'/'.Utilities::getResourceDir(). 'sp-key.key');
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
