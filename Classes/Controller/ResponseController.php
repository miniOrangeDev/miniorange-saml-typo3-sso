<?php
namespace Miniorange\MiniorangeSaml\Controller;

use Exception;
use Miniorange\Helper\Actions\ProcessResponseAction;
use Miniorange\Helper\Actions\ReadResponseAction;
use Miniorange\Helper\Actions\TestResultActions;
use Miniorange\Helper\Constants;
use Miniorange\Helper\Exception\InvalidAudienceException;
use Miniorange\Helper\Exception\InvalidDestinationException;
use Miniorange\Helper\Exception\InvalidIssuerException;
use Miniorange\Helper\Exception\InvalidSamlStatusCodeException;
use Miniorange\Helper\Exception\InvalidSignatureInResponseException;
use Miniorange\Helper\Utilities;
use ReflectionClass;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use Miniorange\Helper\lib\XMLSecLibs\XMLSecurityKey;
use Miniorange\Helper\lib\XMLSecLibs\XMLSecurityDSig;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * ResponseController
 */
class ResponseController extends ActionController
{
//    /**
//     * responseRepository
//     *
//     * @var \Miniorange\MiniorangeSaml\Domain\Repository\ResponseRepository
//     * @inject
//     */
//    protected $responseRepository = null;

//    protected $fesamlRepository = null;

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

    private $x509_certificate;

    /**
     * @inject
     * @param FrontendUserRepository $frontendUserRepository
     */
    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository)
    {
        $this->frontendUserRepository = $frontendUserRepository;
    }

    /**
     * action check
     *
     * @return void
     * @throws InvalidAudienceException
     * @throws InvalidDestinationException
     * @throws InvalidIssuerException
     * @throws InvalidSamlStatusCodeException
     * @throws InvalidSignatureInResponseException
     * @throws \ReflectionException
     */
    public function responseAction()
    {

        $this->cacheService->clearPageCache([$GLOBALS['TSFE']->id]);
        if (array_key_exists('SAMLResponse', $_REQUEST) && !empty($_REQUEST['SAMLResponse'])) {
            $samlResponseObj = ReadResponseAction::execute();
            if ($samlResponseObj != null) {
                $this->control();
                $this->ssoemail = current(current($samlResponseObj->getAssertions())->getNameId());
                $attrs = current($samlResponseObj->getAssertions())->getAttributes();
                $attrs['NameID'] = ['0' => $this->ssoemail];
                $relayStateUrl = array_key_exists('RelayState', $_REQUEST) ? $_REQUEST['RelayState'] : '/';
                if ($relayStateUrl == 'testconfig') {
                    (new TestResultActions($attrs))->execute();
                    die;
                }
                $responseAction = new ProcessResponseAction($samlResponseObj, $this->acs_url, $this->issuer, $this->sp_entity_id, $this->signedResponse, $this->signedAssertion, $this->x509_certificate);
                $responseAction->execute();
                $this->ses_id = current($samlResponseObj->getAssertions())->getSessionIndex();
                $attributes = current($samlResponseObj->getAssertions())->getAttributes();
                if ($attributes != null) {
                    $this->first_name = $attributes[$this->fetch_fname()];
                    $this->last_name = $attributes[$this->fetch_lname()];
                }
                $username = $this->ssoemail;
                $GLOBALS['TSFE']->fe_user->checkPid = 0;
                $info = $GLOBALS['TSFE']->fe_user->getAuthInfoArray();
                $user = $GLOBALS['TSFE']->fe_user->fetchUserRecord($info['db_user'], $username);
                if ($user == null) {
                    $user = $this->create($username);
                    $user = $GLOBALS['TSFE']->fe_user->fetchUserRecord($info['db_user'], $username);
                }

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
                $reflection = new ReflectionClass($GLOBALS['TSFE']->fe_user);
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
     *
     * @param null $instant
     * @return false|string
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
     *
     * @param $bytes
     * @return string
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
     * @return false|string
     */
    function generateRandomBytes($length)
    {
        return openssl_random_pseudo_bytes($length);
    }

    /**
     *
     * @param $username
     * @return FrontendUser
     */
    public function create($username)
    {
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $frontendUser = new FrontendUser();
        $frontendUser->setUsername($username);
        $frontendUser->setFirstName($this->first_name);
        $frontendUser->setLastName($this->last_name);
        $frontendUser->setEmail($username);
        $frontendUser->setPassword('');

        $userGroup = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserGroupRepository')->findByUid(1);
        if($userGroup!=null){
            $frontendUser->addUsergroup($userGroup);
        }

        $this->frontendUserRepository = $objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserRepository')->add($frontendUser);
        $this->persistenceManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager')->persistAll();
        return $frontendUser;
    }

    public function control()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_SAML);
        $this->idp_name = $queryBuilder->select('idp_name')->from(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->acs_url = $queryBuilder->select('acs_url')->from(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->sp_entity_id = $queryBuilder->select('sp_entity_id')->from(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->saml_login_url = $queryBuilder->select('saml_login_url')->from(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->x509_certificate = $queryBuilder->select('x509_certificate')->from(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $this->issuer = $queryBuilder->select('idp_entity_id')->from(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $signedAssertion = true;
        $signedResponse = true;
    }

}
