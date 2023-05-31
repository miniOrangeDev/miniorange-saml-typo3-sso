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
use PDO;
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

use Psr\Http\Message\ResponseFactoryInterface;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Core\Information\Typo3Version;

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

        GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->flushCaches();
        if (array_key_exists('SAMLResponse', $_REQUEST) && !empty($_REQUEST['SAMLResponse'])) {

            $samlResponseObj = ReadResponseAction::execute();

            if ($samlResponseObj != null) {
                $this->control();

                $this->name_id = current(current($samlResponseObj->getAssertions())->getNameId());
                $this->ssoemail = current(current($samlResponseObj->getAssertions())->getNameId());
                $attrs = current($samlResponseObj->getAssertions())->getAttributes();

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
                $tsfe = self::getTypoScriptFrontendController();
                $tsfe->fe_user->checkPid = 0;
                $user = $this->createIfNotExist($username);
                $_SESSION['ses_id'] = $user['uid'];
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_sessions');

                $version = new Typo3Version();
                if( $version->getVersion() >=11.0)
                {
                    //$queryBuilder->delete('fe_sessions')->where($queryBuilder->expr()->eq('ses_userid',$queryBuilder->createNamedParameter($user['uid'], \PDO::PARAM_INT)))->executeStatement(); 
                    $queryBuilder->delete('fe_sessions')->where($queryBuilder->expr()->eq('ses_userid',$queryBuilder->createNamedParameter($user['uid'], \PDO::PARAM_INT)))->execute();
                }
                $context = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
                $context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
                $tsfe->fe_user->forceSetCookie = TRUE;
                $tsfe->fe_user->start();
                $tsfe->fe_user->createUserSession($user);
                $tsfe->fe_user->user = $user;

                $tsfe->initUserGroups();
                $tsfe->fe_user->loginSessionStarted = TRUE;

                $tsfe->fe_user->setKey('user', 'fe_typo_user', $user);
                $GLOBALS['TSFE']->fe_user->setKey('ses', 'fe_typo_user', $user);
                $ses_id = $tsfe->fe_user->fetchUserSession();

                $reflection = new ReflectionClass($tsfe->fe_user);
                $setSessionCookieMethod = $reflection->getMethod('setSessionCookie');
                $setSessionCookieMethod->setAccessible(TRUE);
                $setSessionCookieMethod->invoke($tsfe->fe_user);
                
                $tsfe->fe_user->storeSessionData();

                if (!isset($_SESSION)) {
                      session_id('email');
                      session_start();
                      $_SESSION['email'] = $this->ssoemail;
                      $_SESSION['id'] = $ses_id;
                }
                GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->flushCaches();
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
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $user = Utilities::fetchUserFromUsername($username);
        if($user == false){
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_SAML);
            $count = $queryBuilder->select('countuser')->from(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
            if($count>0)
            {
                $frontendUser = new FrontendUser();
                $frontendUser->setUsername($username);
                $fnamelname = explode("@", $username);
                $this->first_name = $fnamelname[0];
                $this->last_name = $fnamelname[1];
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
                $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set('countuser', $count-1)->execute();
                //$queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set('countuser', $count-1)->executeQuery();
                $this->frontendUserRepository = $objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserRepository')->add($frontendUser);
                $this->persistenceManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager')->persistAll();
                $user = Utilities::fetchUserFromUsername($username);
                return $user;
            }
            else
            {
                echo "User limit exceeded!!! Please upgrade to the Premium Plan in order to continue the services";exit;
            }
        }
        else{
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
        //$queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->set('custom_attr', $val)->executeQuery();
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
        $sp_object = $queryBuilder->select('spobject')->from(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $idp_object = $queryBuilder->select('object')->from(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        $sp_object = json_decode($sp_object,true);
        $idp_object = json_decode($idp_object,true);
        $this->idp_name = $idp_object['idp_name'];
        $this->acs_url = $sp_object['acs_url'];
        $this->sp_entity_id = $sp_object['sp_entity_id'];
        $this->saml_login_url = $idp_object['saml_login_url'];
        $this->x509_certificate = $idp_object['x509_certificate'];
        $this->issuer = $idp_object['idp_entity_id'];
        $signedAssertion = true;
        $signedResponse = true;
    }

        /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

}
