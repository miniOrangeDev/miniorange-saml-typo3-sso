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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Cache\CacheManager;
use Miniorange\Sp\Helper\CustomerSaml;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Context\User\FrontendUserAspect;
use TYPO3\CMS\Core\Session\Frontend\AnonymousSession;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Session\UserSession;
use Psr\Http\Message\ResponseInterface;
use Miniorange\Sp\Helper\AESEncryption;

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
    private $issuer = null;
    private $signedAssertion = null;
    private $signedResponse = null;
    private $name_id;
    private $status;
    private $ssoemail = null;
    private $username = null;
    private $ses_id = null;
    private $attrsReceived = null;
    private $amObject = null;
    private $idpObject = null;
    private $spObject = null;
    protected $x509_certificate;
    protected $fe_user;

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
                $user = $this->createOrUpdateUser($username, $typo3Version);
                $this->login_user($user, $relayStateUrl);
            }
        }

        if ($typo3Version >= 11.5) {
            $responseFactory = GeneralUtility::makeInstance(\Psr\Http\Message\ResponseFactoryInterface::class);
            $streamFactory = GeneralUtility::makeInstance(\Psr\Http\Message\StreamFactoryInterface::class);
            $response = $responseFactory->createResponse()
                ->withAddedHeader('Content-Type', 'text/html; charset=utf-8')
                ->withBody($streamFactory->createStream($this->view->render()));
            return $response;
        }
    }

        /**
     * This function handles frontend user login.
     * @param mixed $username
     * @param mixed $id_token
     * @param mixed $apptype
     * @param mixed $currentapp
     * @return void
     */
    function login_user($user, $relayStateUrl)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('fe_sessions');

        $queryBuilder->delete('fe_sessions')
            ->where(
                $queryBuilder->expr()->eq('ses_userid', $queryBuilder->createNamedParameter($user['uid'], \Doctrine\DBAL\ParameterType::INTEGER))
            );

        $queryBuilder->executeStatement(); 
        $frontendUser = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::class);
        $frontendUser->start($this->request);
        $data = [
            'nameId' => $this->ssoemail
        ];

        // Create user session (this handles all internal properties correctly)
        $session = $frontendUser->createUserSession($user);
        $sessionId = $session->getIdentifier();

        // Get session manager to update the session with data
        $sessionManager = GeneralUtility::makeInstance(SessionManager::class);
        $sessionBackend = $sessionManager->getSessionBackend('FE');

        // Get current session data and add your data
        $currentSessionData = $session->getData();
        $currentSessionData['ses_data'] = serialize($data);

        // Update the session
        $sessionBackend->update($sessionId, $currentSessionData);

        // Store session data (this also handles internal state)
        $frontendUser->storeSessionData();
        
        // Create a secure cookie with HttpOnly, Secure, and SameSite parameters
        setcookie("fe_typo_user", $session->getJwt(), 0, '/');
        header('location: ' . $relayStateUrl);
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
            $encryptedCount = Utilities::fetchFromTable(Constants::COLUMN_COUNTUSER,Constants::TABLE_SAML);
            $count = AESEncryption::decrypt_data($encryptedCount, Constants::ENCRYPT_TOKEN);
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
                Utilities::updateTableSaml(Constants::COLUMN_COUNTUSER, AESEncryption::encrypt_data((int)$count-1, Constants::ENCRYPT_TOKEN));
            } else {
                $customer = new CustomerSaml();
                $timestamp = Utilities::fetch_cust(Constants::TIMESTAMP);
                $data = [
                    'timeStamp' => $timestamp,
                    'autoCreateLimit' => 'Yes'
                ];
                $customer->syncPluginMetrics($data);
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
            $uid = $queryBuilder->select('uid')->from(Constants::TABLE_FE_USERS)->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username, Connection::PARAM_STR)))->executeQuery()->fetchAssociative();
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
