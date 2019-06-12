<?php
namespace MiniOrange\MiniorangeSaml\Controller;
use MiniOrange\Classes\SamlResponse;
use MiniOrange\Classes\Actions;
use MiniOrange\Classes\Actions\ProcessResponseAction;
use MiniOrange\Classes\Actions\ProcessUserAction;
use MiniOrange\Classes\Actions\ReadResponseAction;
use MiniOrange\Classes\Actions\TestResultActions;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use MiniOrange\Helper\Lib\XMLSecLibs\XMLSecurityKey;
use TYPO3\CMS\Felogin\Controller\FrontendLoginController;
use MiniOrange\Helper\Lib\XMLSecLibs;
use MiniOrange\Helper\Lib\XMLSecLibs\XMLSecurityDSig;
use MiniOrange\Helper\Utilities;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Session;
use TYPO3\CMS\Extbase\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Authentication\FrontenduserAuthentication;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Core\Cache\CacheManager;
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
 * ResponseController
 */
class ResponseController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * responseRepository
     * 
     * @var \MiniOrange\MiniorangeSaml\Domain\Repository\ResponseRepository
     * @inject
     */
    protected $responseRepository = null;

    protected $fesamlRepository = null;

    protected $idp_name = null;

    protected $acs_url = null;

    protected $sp_entity_id = null;

    protected $force_authn = null;

    protected $saml_login_url = null;

    private $issuer = null;

    private $ssoUrl = null;

    private $bindingType = null;

    private $first_name = null;

    private $last_name = null;

    private $signedAssertion = null;

    private $signedResponse = null;

    protected $persistenceManager = null;

    protected $frontendUserRepository = null;

    private $ssoemail = null;

    private $ses_id = null;

    /**
     * action check
     * 
     * @return void
     */
    public function checkAction()
    {
        $caches = new TypoScriptTemplateModuleController();
        $caches->clearCache();
        $this->cacheService->clearPageCache([$GLOBALS['TSFE']->id]);
        if (array_key_exists('SAMLResponse', $_REQUEST) && !empty($_REQUEST['SAMLResponse'])) {
            $samlResponseObj = ReadResponseAction::execute();
            if ($samlResponseObj != null) {
                $this->control();
                $this->ssoemail = current(current($samlResponseObj->getAssertions())->getNameId());
                $attrs = current($samlResponseObj->getAssertions())->getAttributes();
                $responseAction = new ProcessResponseAction($samlResponseObj, $this->acs_url, $this->issuer, $this->sp_entity_id, $this->signedResponse, $this->signedAssertion, $this->x509_certificate);
                $responseAction->execute();
                $attrs['NameID'] = ['0' => $this->ssoemail];
                $relayStateUrl = array_key_exists('RelayState', $_REQUEST) ? $_REQUEST['RelayState'] : '/';
                if ($relayStateUrl == 'testconfig') {
                    (new TestResultActions($attrs))->execute();
                    die;
                }

                $this->ses_id = current($samlResponseObj->getAssertions())->getSessionIndex();
                $username = $this->ssoemail;
                $GLOBALS['TSFE']->fe_user->checkPid = 0;
                $info = $GLOBALS['TSFE']->fe_user->getAuthInfoArray();
                $user = $GLOBALS['TSFE']->fe_user->fetchUserRecord($info['db_user'], $username);
                if ($user == null) {
                    $user = $this->create($username);
                    $user = $GLOBALS['TSFE']->fe_user->fetchUserRecord($info['db_user'], $username);
                }
//                               $session = $GLOBALS['TSFE']->fe_user->user['ses_id'];
//                                setcookie('fe_typo_user', $session, NULL, "/typo1");
//                                $GLOBALS['TSFE']->fe_user->checkPid = 0;
//                                $GLOBALS['TSFE']->fe_user->dontSetCookie = FALSE;
//                                $GLOBALS['TSFE']->fe_user->user = $GLOBALS['TSFE']->fe_user->fetchUserSession();
//                                $GLOBALS['TSFE']->loginUser = 1;
//                                $GLOBALS['TSFE']->fe_user->start();
//                                $GLOBALS['TSFE']->fe_user->createUserSession($user);
//                                $GLOBALS['TSFE']->initUserGroups();
//                                $GLOBALS['TSFE']->fe_user->loginSessionStarted = TRUE;
//                                $GLOBALS['TSFE']->storeSessionData();
                $GLOBALS['TSFE']->fe_user->forceSetCookie = TRUE;
                $GLOBALS['TSFE']->fe_user->loginUser = 1;
                $GLOBALS['TSFE']->fe_user->start();
                $GLOBALS['TSFE']->fe_user->createUserSession($user);
                $GLOBALS['TSFE']->initUserGroups();
                $GLOBALS['TSFE']->fe_user->loginSessionStarted = TRUE;
                $GLOBALS['TSFE']->fe_user->user = $user;
                $GLOBALS['TSFE']->fe_user->setKey('user', 'fe_typo_user', $user);
                //$GLOBALS['TSFE']->fe_user->user = $GLOBALS['TSFE']->fe_user->fetchUserSession();
                $GLOBALS['TSFE']->fe_user->setAndSaveSessionData('user', TRUE);
                $this->ses_id = $GLOBALS['TSFE']->fe_user->fetchUserSession();
                $reflection = new \ReflectionClass($GLOBALS['TSFE']->fe_user);
                $setSessionCookieMethod = $reflection->getMethod('setSessionCookie');
                $setSessionCookieMethod->setAccessible(TRUE);
                $setSessionCookieMethod->invoke($GLOBALS['TSFE']->fe_user);
                $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysFetchUser'] = true;
                $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysAuthUser'] = true;
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed'] = true;
                $GLOBALS['TSFE']->fe_user->storeSessionData();

                if (!isset($_SESSION)) {
                    session_id('email');
                    session_start();
                    $_SESSION['email'] = $this->ssoemail;
                    $_SESSION['id'] = $this->ses_id;
                }
                $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
                \TYPO3\CMS\Core\Utility\HttpUtility::redirect($actual_link);
            }
        }

    }
    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository)
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }
    public function fetch_fname()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $fname = $queryBuilder->select('saml_am_fname')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        return $fname;
    }

    public function fetch_lname()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $lname = $queryBuilder->select('saml_am_lname')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        return $lname;
    }

    /**
     * @param $val
     */
    public function setFlag($val)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->set('custom_attr', $val)->execute();
    }

    /**
     * @param $ses_id
     * @param $ssoemail
     */
    public function logout($ses_id, $ssoemail)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $logout_url = $queryBuilder->select('saml_logout_url')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        if (isset($_REQUEST['SAMLResponse'])) {
            $samlResponse = $_REQUEST['SAMLResponse'];
            $samlResponse = base64_decode($samlResponse);
            if (array_key_exists('SAMLResponse', $_GET) && !empty($_GET['SAMLResponse'])) {
                $samlResponse = gzinflate($samlResponse);
            }
            $document = new \DOMDocument();
            $document->loadXML($samlResponse);
            $samlResponseXml = $document->firstChild;
            $doc = $document->documentElement;
            $xpath = new \DOMXpath($document);
            $xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
            $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
            if ($samlResponseXml->localName == 'LogoutResponse') {
                header('Location: ' . $logout_url . '?slo=success');
                die;
            }
        }
        if (session_status() == PHP_SESSION_NONE) {
            session_id('attributes');
            session_start();
        }
        if (!empty($logout_url)) {
            $nameId = $this->ssoemail;
            $issuer = $this->sp_entity_id;
            $single_logout_url = $logout_url;
            $destination = $single_logout_url;
            // $sessionIndex = '';
            $sessionIndex = $ses_id;
            //'_d82a4777-6297-4dfe-811e-2c34dfe43f00';
            // $sendRelayState = $logout_url;
            $sendRelayState = 'test';
            $samlRequest = $this->createLogoutRequest($nameId, $sessionIndex, $issuer, $destination, 'HttpRedirect');
            $samlRequest = 'SAMLRequest=' . $samlRequest . '&RelayState=' . urlencode($sendRelayState) . '&SigAlg=' . urlencode(XMLSecurityKey::RSA_SHA256);
            $param = ['type' => 'private'];
            $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, $param);
            // $certFilePath = . DIRECTORY_SEPARATOR . 'sp-key.key';
            $certFilePath = file_get_contents(__DIR__ . '/../../sso/resources/sp-key.key');
            $key->loadKey($certFilePath);
            $objXmlSecDSig = new XMLSecurityDSig();
            $signature = $key->signData($samlRequest);
            $signature = base64_encode($signature);
            $redirect = $single_logout_url . '?' . $samlRequest . '&Signature=' . urlencode($signature);
        }
        if (!empty($logout_url)) {
            session_destroy();
        }
    }

    /**
     * @param $nameId
     * @param $sessionIndex
     * @param $issuer
     * @param $destination
     * @param $slo_binding_type
     */
    public function createLogoutRequest($nameId, $sessionIndex = '', $issuer, $destination, $slo_binding_type = 'HttpRedirect')
    {
        $requestXmlStr = '<?xml version="1.0" encoding="UTF-8"?>' . '<samlp:LogoutRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="' . $this->generateID() . '" IssueInstant="' . $this->generateTimestamp() . '" Version="2.0" Destination="' . $destination . '">
						<saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">http://localhost/typo3/some-connector-id</saml:Issuer>
						<saml:NameID xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">' . $nameId . '</saml:NameID>';
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

    /**
     * @param $instant
     */
    function generateTimestamp($instant = NULL)
    {
        if ($instant === NULL) {
            $instant = time();
        }
        return gmdate('Y-m-d\\TH:i:s\\Z', $instant);
    }

    function generateID()
    {
        return '_' . $this->stringToHex($this->generateRandomBytes(21));
    }

    /**
     * @param $bytes
     */
    function stringToHex($bytes)
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
    function generateRandomBytes($length, $fallback = TRUE)
    {
        return openssl_random_pseudo_bytes($length);
    }

    /**
     * @param $username
     */
    public function create($username)
    {
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
//
//        //$newUser = $this->objectManager->get('Fhk\\Feusersplus\\Domain\\Repository\\UserRepository');
//
//        $userModel = $this->objectManager->get('Fhk\\Feusersplus\\Domain\\Model\\User');
        $userGroup = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserGroupRepository');
//
//
//        $userModel = new \Typo3\CMS\Extbase\Domain\Model\FrontendUser();
//
//        $userModel->setUsername("sdfdsfdsfs");
//        $userModel->setEmail($this->ssoemail);
//        $gender = 0;
//        $password = rand(99999999,9999999999999);
//        $userModel->setPassword($password);
//        $userModel->setPid(3);
//
//
//        $this->FrontendUserRepository->add($userModel);
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $frontendUser = new FrontendUser();
        $frontendUser->setUsername($username);
        $frontendUser->setFirstName($this->first_name);
        $frontendUser->setLastName($this->last_name);
        $frontendUser->setEmail($username);
        $frontendUser->setPassword('demouser');
        $userGroup = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserGroupRepository')->findByUid(1);
        $frontendUser->addUsergroup($userGroup);
        $this->frontendUserRepository = $objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserRepository')->add($frontendUser);
        $this->persistenceManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager')->persistAll();
        return $frontendUser;
    }

    public function control()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $this->idp_name = $queryBuilder->select('idp_name')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->acs_url = $queryBuilder->select('acs_url')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->sp_entity_id = $queryBuilder->select('sp_entity_id')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->saml_login_url = $queryBuilder->select('saml_login_url')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        //$this->force_authn = $queryBuilder->select('force_authn')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->x509_certificate = $queryBuilder->select('x509_certificate')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->issuer = $queryBuilder->select('idp_entity_id')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $signedAssertion = true;
        $signedResponse = true;
    }

}
