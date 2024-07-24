<?php

namespace Miniorange\Sp\Controller;

use Exception;
use Miniorange\Sp\Helper\Actions\ProcessResponseAction;
use Miniorange\Sp\Helper\Actions\ReadResponseAction;
use Miniorange\Sp\Helper\Actions\TestResultActions;
use Miniorange\Sp\Helper\Constants;
use MiniOrange\Helper\Exception\InvalidAudienceException;
use MiniOrange\Helper\Exception\InvalidDestinationException;
use MiniOrange\Helper\Exception\InvalidIssuerException;
use MiniOrange\Helper\Exception\InvalidSamlStatusCodeException;
use MiniOrange\Helper\Exception\InvalidSignatureInResponseException;
use Miniorange\Sp\Helper\SAMLUtilities;
use Miniorange\Sp\Helper\Utilities;
use PDO;
use ReflectionClass;
use ReflectionException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;
use Psr\Http\Message\ResponseFactoryInterface;
use TYPO3\CMS\Core\Session\UserSessionManager;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Core\Information\Typo3Version;

use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Cache\CacheManager;
use Miniorange\Sp\Helper\CustomerSaml;


/**
 * ResponseController
 */
class ResponseController extends ActionController
{

    protected $idp_name = null;

    protected $acs_url = null;

    protected $sp_entity_id = null;

    protected $force_authn = null;

    protected $saml_login_url = null;
    protected $persistenceManager = null;
    protected $frontendUserRepository = null;
    protected $responseFactory = null;
    private $issuer = null;
    private $signedAssertion = null;
    private $signedResponse = null;
    private $ssoemail = null;
    private $username = null;
    private $ses_id = null;
    private $attrsReceived = null;
    private $amObject = null;
    private $idpObject = null;
    private $spObject = null;

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
     * @throws Exception
     */
    public function responseAction()
    {
        $version = new Typo3Version();
        $typo3Version = $version->getVersion();
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
                $user = $this->createOrUpdateUser($username, $typo3Version);
                $_SESSION['ses_id'] = $user['uid'];
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_sessions');

                if ($typo3Version >= 12) {
                    $queryBuilder->delete('fe_sessions')->where($queryBuilder->expr()->eq('ses_userid', $queryBuilder->createNamedParameter($user['uid'], \PDO::PARAM_INT)))->executeStatement();
                }
                else
                {
                    $queryBuilder->delete('fe_sessions')->where($queryBuilder->expr()->eq('ses_userid', $queryBuilder->createNamedParameter($user['uid'], \PDO::PARAM_INT)))->execute();
                }
                $context = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
                $context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
                $tsfe->fe_user->forceSetCookie = TRUE;
                $tsfe->fe_user->createUserSession($user);
                $tsfe->fe_user->user = $user;

                $tsfe->initUserGroups();
                $tsfe->fe_user->loginSessionStarted = TRUE;
                $reflection = new ReflectionClass($tsfe->fe_user);
                $setSessionCookieMethod = $reflection->getMethod('setSessionCookie');
                $setSessionCookieMethod->setAccessible(TRUE);
                $setSessionCookieMethod->invoke($tsfe->fe_user);

                if (!isset($_SESSION)) {
                    session_id('email');
                    session_start();
                    $_SESSION['email'] = $this->ssoemail;
                    $_SESSION['id'] = $ses_id;
                }
                GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->flushCaches();
            }
        }

        if ($typo3Version >= 11.5) {
            return $this->responseFactory->createResponse()
                ->withAddedHeader('Content-Type', 'text/html; charset=utf-8')
                ->withBody($this->streamFactory->createStream($this->view->render()));
        }
    }

    public function control()
    {
        $sp_object = Utilities::fetchFromTable(Constants::SAML_SPOBJECT, Constants::TABLE_SAML);
        $idp_object = Utilities::fetchFromTable(Constants::SAML_IDPOBJECT, Constants::TABLE_SAML);
        $sp_object = !is_array($sp_object) ? json_decode($sp_object, true) : $sp_object;
        $idp_object = !is_array($idp_object) ? json_decode($idp_object, true) : $idp_object;
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

    /**
     * @param $username
     * @return array
     */
    public function createOrUpdateUser($username, $typo3Version)
    {
        $user = Utilities::fetchUserFromUsername($username);
        $userExist = false;
        if ($user == false) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
            $count = Utilities::fetchFromTable(Constants::COLUMN_COUNTUSER,Constants::TABLE_SAML);
            if ($count > 0) {
                Utilities::log_php_error("CREATING USER", $username);

                $newUser = [
                    'username' => $username,
                    'password' => SAMLUtilities::generateRandomAlphanumericValue(10),
                ];

                // Insert the new user into the fe_users table
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');
                if($typo3Version > 12)
                {
                    $queryBuilder
                    ->insert('fe_users')
                    ->values($newUser)
                    ->executeStatement();
                }
                else
                {
                    $queryBuilder
                    ->insert('fe_users')
                    ->values($newUser)
                    ->execute();
                }

                // Output the UID of the newly created user
                $uid = $queryBuilder->getConnection()->lastInsertId('fe_users');
                Utilities::updateTableSaml(Constants::COLUMN_COUNTUSER, $count-1);
            } else {
                $autocreate_exceed_email_sent = Utilities::fetchFromTable(Constants::AUTOCREATE_EXCEED_EMAIL_SENT, Constants::TABLE_SAML);
                $site = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
                $customer = new CustomerSaml();
                if($autocreate_exceed_email_sent == NULL)
                {
                    $customer->submit_to_magento_team_autocreate_limit_exceeded($site, $typo3Version);
                    Utilities::updateTableSaml(Constants::AUTOCREATE_EXCEED_EMAIL_SENT, 1);
                }
                echo "User limit exceeded!!! Please upgrade to the Premium Plan in order to continue the services";
                exit;
            }

        } else {
            Utilities::log_php_error("USER EXISTS: ", $username);
            if ($user['disable'] == 1) {
                Utilities::log_php_error("USER EXISTS BUT IS DISABLED", $username);
                exit("You are not allowed to login. Please contact your admin.");
            }
            $userExist = true;
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');
            $uid = $queryBuilder->select('uid')->from(Constants::TABLE_FE_USERS)->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username, \PDO::PARAM_STR)))->executeQuery()->fetch();
            $uid = is_array($uid) ? $uid['uid'] : $uid;
        }

        GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->flushCaches();
        // add or assign default group
        error_log("GroupAttribute not mapped in Attribute Mapping Tab.");
        if (!$userExist) {
            error_log("New User: Assigning Default Group.");
            $mappedTypo3Group = Utilities::fetchFromTable(Constants::COLUMN_GROUP_DEFAULT, Constants::TABLE_SAML);
            Utilities::log_php_error("Assigning DEFAULT group to user: ", $mappedTypo3Group);

            if (!$mappedTypo3Group) {
                exit("Unable to assign user to default group. Please contact your admin." . $mappedTypo3Group);
            }
            $mappedGroupUid = Utilities::fetchUidFromGroupName($mappedTypo3Group);
            $mappedGroupUid = $mappedGroupUid;
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');
            if($typo3Version > 12)
            {
                $queryBuilder->update(Constants::TABLE_FE_USERS)->where($queryBuilder->expr()->eq('uid', $uid))
                ->set('usergroup', $mappedGroupUid)->executeStatement();
            }
            else
            {
                $queryBuilder->update(Constants::TABLE_FE_USERS)->where($queryBuilder->expr()->eq('uid', $uid))
                ->set('usergroup', $mappedGroupUid)->execute();
            }
            GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->flushCaches();

        } else {
            Utilities::log_php_error("group mapping done");
            //mapping is Correct Groups are received but No group is assigned to User. Use default group.
            error_log("No groups received is assigned to User. So assigning Default Group to existing user.");
            $mappedTypo3Group = Utilities::fetchFromTable(Constants::COLUMN_GROUP_DEFAULT, Constants::TABLE_SAML);
            Utilities::log_php_error("Assigning DEFAULT group to user: ", $mappedTypo3Group);

            if (!$mappedTypo3Group) {
                exit("Unable to assign user to default group. Please contact your admin." . $mappedTypo3Group);
            }
            $mappedGroupUid = Utilities::fetchUidFromGroupName($mappedTypo3Group);
            $mappedGroupUid = $mappedGroupUid;
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');
            Utilities::log_php_error("assigning default group ");
            if($typo3Version > 12)
            {
                $queryBuilder->update(Constants::TABLE_FE_USERS)->where($queryBuilder->expr()->eq('uid', $uid))
                ->set('usergroup', $mappedGroupUid)->executeStatement();
            }
            else
            {
                $queryBuilder->update(Constants::TABLE_FE_USERS)->where($queryBuilder->expr()->eq('uid', $uid))
                ->set('usergroup', $mappedGroupUid)->execute();
            }
            Utilities::log_php_error("assigned default group");
        }
        Utilities::log_php_error("fetching user from username: ");
        $user = Utilities::fetchUserFromUsername($username);
        Utilities::log_php_error("User fetched");
        return $user;
    }

    /**
     * @param $instant
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
     * @param $fallback
     * @return false|string
     */
    function generateRandomBytes($length, $fallback = TRUE)
    {
        return openssl_random_pseudo_bytes($length);
    }

}
