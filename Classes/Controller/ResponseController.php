<?php
namespace Miniorange\MiniorangeSaml\Controller;

use Miniorange\Helper\Actions\ProcessResponseAction;
use Miniorange\Helper\Actions\ReadResponseAction;
use Miniorange\Helper\Actions\TestResultActions;
use Miniorange\Helper\Constants;
use Miniorange\Helper\Exception\InvalidAudienceException;
use Miniorange\Helper\Exception\InvalidDestinationException;
use Miniorange\Helper\Exception\InvalidIssuerException;
use Miniorange\Helper\Exception\InvalidSamlStatusCodeException;
use Miniorange\Helper\Exception\InvalidSignatureInResponseException;
use Miniorange\Helper\SAMLUtilities;
use Miniorange\Helper\Utilities;
use ReflectionClass;
use ReflectionException;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;
use Miniorange\MiniorangeSaml\Service\LoginUser;

use Exception;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use Miniorange\Helper\lib\XMLSecLibs\XMLSecurityKey;
use Miniorange\Helper\lib\XMLSecLibs\XMLSecurityDSig;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Extbase\Annotation\Inject;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * ResponseController
 */
class ResponseController extends ActionController
{

    protected $idp_name = null;
    protected $acs_url = null;
    protected $sp_entity_id = null;
    protected $saml_login_url = null;
    private $issuer = null;

    private $signedAssertion = null;
    private $signedResponse = null;
    protected $persistenceManager = null;
    protected $frontendUserRepository = null;

    private $name_id = null;
    private $ssoemail = null;
    private $x509_certificate;

//    /**
//     * @inject
//     * @param FrontendUserRepository $frontendUserRepository
//     */
//    public function injectFrontendUserRepository(FrontendUserRepository $frontendUserRepository)
//    {
//        $this->frontendUserRepository = $frontendUserRepository;
//    }

    /**
     * action check
     *
     * @return void
     * @throws InvalidAudienceException
     * @throws InvalidDestinationException
     * @throws InvalidIssuerException
     * @throws InvalidSamlStatusCodeException
     * @throws InvalidSignatureInResponseException
     * @throws ReflectionException
     */
    public function responseAction()
    {

        $this->cacheService->clearPageCache([$GLOBALS['TSFE']->id]);

        error_log('request in saml response: '.print_r($_REQUEST,true));
        if (array_key_exists('SAMLResponse', $_REQUEST) && !empty($_REQUEST['SAMLResponse'])) {

            $samlResponseObj = ReadResponseAction::execute();

            if ($samlResponseObj != null) {
                $this->control();

                $this->name_id = current(current($samlResponseObj->getAssertions())->getNameId());
                $this->ssoemail = current(current($samlResponseObj->getAssertions())->getNameId());
                $attrs = current($samlResponseObj->getAssertions())->getAttributes();
                Utilities::log_php_error("idp attribute",$attrs);

                $attrs['NameID'] = ['0' => $this->name_id];
                $relayStateUrl = array_key_exists('RelayState', $_REQUEST) ? $_REQUEST['RelayState'] : '/';
                
                if ($relayStateUrl == 'testconfig') {
                    (new TestResultActions($attrs))->execute();
                    die;
                }

                $responseAction = new ProcessResponseAction($samlResponseObj, $this->acs_url, $this->issuer, $this->sp_entity_id, $this->signedResponse, $this->signedAssertion, $this->x509_certificate);
                $responseAction->execute();
                $ses_id = current($samlResponseObj->getAssertions())->getSessionIndex();
                $username = $this->ssoemail;
                $GLOBALS['TSFE']->fe_user->checkPid = 0;

                $user = $this->createIfNotExist($username);

                $context = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
                $GLOBALS['TSFE']->fe_user->forceSetCookie = TRUE;
                $context->getPropertyFromAspect('frontend.user', 'isLoggedIn');

                $GLOBALS['TSFE']->fe_user->start();
                $GLOBALS['TSFE']->fe_user->createUserSession($user);
                $GLOBALS['TSFE']->initUserGroups();
                $GLOBALS['TSFE']->fe_user->loginSessionStarted = TRUE;
                $GLOBALS['TSFE']->fe_user->user = $user;
                $GLOBALS['TSFE']->fe_user->setKey('user', 'fe_typo_user', $user);

                $GLOBALS['TSFE']->fe_user->setAndSaveSessionData('user', TRUE);
                $ses_id = $GLOBALS['TSFE']->fe_user->fetchUserSession();
                $reflection = new ReflectionClass($GLOBALS['TSFE']->fe_user);
                $setSessionCookieMethod = $reflection->getMethod('setSessionCookie');
                $setSessionCookieMethod->setAccessible(TRUE);
                $setSessionCookieMethod->invoke($GLOBALS['TSFE']->fe_user);
                $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysFetchUser'] = true;
                $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_alwaysAuthUser'] = true;
                $GLOBALS['TSFE']->fe_user->storeSessionData();

                if (!isset($_SESSION)) {
                      session_id('email');
                      session_start();
                      $_SESSION['email'] = $this->ssoemail;
                      $_SESSION['id'] = $ses_id;
                }

               $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
               \TYPO3\CMS\Core\Utility\HttpUtility::redirect($actual_link);

            }
        }

    }

    /**
     *
     * @param $username
     * @return bool
     */
    public function createIfNotExist($username)
    {
//     $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $user = Utilities::fetchUserFromUsername($username);
        debug("User Search 1 : ".$user);
        if($user == false){
            $frontendUser = new FrontendUser();
            $frontendUser->setUsername($username);

            $frontendUser->setFirstName($this->first_name);
            $frontendUser->setLastName($this->last_name);
            $frontendUser->setEmail($this->ssoemail);
            $frontendUser->setPassword(SAMLUtilities::generateRandomAlphanumericValue(10));  //Setting Random Password

            $mappedGroupUid = Utilities::fetchUidFromGroupName(Utilities::fetchFromTable(Constants::COLUMN_GROUP_DEFAULT,Constants::TABLE_SAML));
            $userGroup = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserGroupRepository')->findByUid($mappedGroupUid);

            if($userGroup!=null){
                $frontendUser->addUsergroup($userGroup);
            }else{
                exit("Unable to create User. No UserGroup found.");
            }

            $this->frontendUserRepository = $objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserRepository')->add($frontendUser);
            $this->persistenceManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager')->persistAll();
            $user = Utilities::fetchUserFromUsername($username);
            debug("User Search 1 : ".$user);

            return $user;

        }else{
            if($user['disable'] == 1){
                exit("You are not allowed to login. Please contact your admin.");
            }
            return $user;
        }

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
