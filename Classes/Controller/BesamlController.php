<?php
namespace MiniOrange\MiniorangeSaml\Controller;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\Renderer\ListRenderer;
use TYPO3\CMS\Core\Messaging\Renderer\PlaintextRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use MiniOrange\Classes\CustomerSaml as CustomerSaml;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use MiniOrange\Helper;
use MiniOrange\Helper\PluginSettings;
use TYPO3\CMS\Core\Messaging\FlashMessageRendererResolver;
use TYPO3\CMS\Core\Messaging\Renderer\BootstrapRenderer;
use TYPO3\CMS\Fluid\ViewHelpers\FlashMessagesViewHelper;
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
 * BesamlController
 */
class BesamlController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * besamlRepository
     *
     * @var \\MiniOrange\MiniorangeSaml\Domain\Repository\BesamlRepository
     * @inject
     */
    protected $besamlRepository = null;

    private $myjson = null;

    private $myattrjson = null;

    private $custom_attr = null;

    private $spobject = null;

    protected $sp_entity_id = null;

    protected $site_base_url = null;

    protected $acs_url = null;

    protected $slo_url = null;

    protected $fesaml = null;

    protected $response = null;

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $besamls = $this->besamlRepository->findAll();
        $this->view->assign('besamls', $besamls);
    }

    /**
     * action show
     *
     * @param \\MiniOrange\MiniorangeSaml\Domain\Model\Besaml $besaml
     * @return void
     */
    public function showAction(MiniOrange\MiniorangeSaml\Domain\Model\Besaml $besaml)
    {
        $this->view->assign('besaml', $besaml);
    }

    /**
     * @param \\MiniOrange\MiniorangeSaml\Domain\Model\Besaml $besaml
     */
    public function requestAction()
    {

        if ($_POST['idp_name'] != null || $_POST['idp_entity_id'] != null || $_POST['saml_login_url'] != null) {
            $obj = new BesamlController();
            $obj->storeToDatabase($_POST);
        }
        if ( isset( $_POST['option'] ) and $_POST['option'] == "mo_saml_contact_us_query_option" ) {
            $this->support();
        }

        if ($_POST['site_base_url'] != null || $_POST['acs_url'] != null || $_POST['sp_entity_id'] != null || $_POST['slo_url'] != null) {
            if($this->fetch('uid') == null)
            {$this->save('uid',1,'saml');}
            $this->defaultSettings($_POST);
        }
        if(isset( $_POST['option'] ) && $_POST['option'] == "mo_saml_register_customer"){
            $id=$this->fetch_cust('id');
            if($id == null)
            {
                $this->save('id',1,'registeration');
            }
            $email = $_POST['email'];
            $password = stripslashes($_POST['password']);
            $confirmPassword = stripslashes($_POST['confirmPassword']);
            $this->registeration($email,$password,$confirmPassword);

        }
        $this->view->assign('conf', json_decode($this->fetch('object'), true));
        $this->view->assign('conf2', json_decode($this->fetch('spobject')), true);
        $this->view->assign('email',$this->fetch_cust('cust_email'));
        $this->view->assign('key',$this->fetch_cust('cust_key'));
        $caches = new TypoScriptTemplateModuleController();
        $caches->clearCache();
        $this->cacheService->clearPageCache([$GLOBALS['TSFE']->id]);
        //$this->flushCaches();

    }
    public function mo_saml_is_curl_installed() {
        if ( in_array( 'curl', get_loaded_extensions() ) ) {
            return 1;
        } else {
            return 0;
        }
    }
    public function registeration($email,$password,$confirmPassword)
    {
        if (strcmp($password, $confirmPassword) == 0) {
            $customer = new CustomerSaml();
            $customer->email = $email;
            //$this->update_cust('cust_email',$email);
            $check_content = json_decode($customer->check_customer($email,$password,$confirmPassword), true);
//                $this->addFlashMessage(
//                    $messageBody = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:yourextkey/Resources/Private/Language/locallang.xlf:error_body', 'miniorange_saml'),
//                    $messageTitle = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT: yourextkey/Resources/Private/Language/locallang.xlf:error_title', ''),
//                    $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR,
//                    $storeInSession = TRUE
//                );

            if (strcasecmp($check_content['status'], 'CUSTOMER_NOT_FOUND') == 0) {
                $create_content = json_decode($customer->create_customer($email,$password,$confirmPassword),true); // Send create call to miniorange and check response content
                if (strcasecmp($create_content['status'], 'CUSTOMER_USERNAME_ALREADY_EXISTS') == 0){
                    $key_content = json_decode($customer->get_customer_key($email,$password), true);
                    if(isset($key_content['id'])){
                        //$this->update_cust('cust_email',$email);
                        $this->save_customer($key_content,$email);
                        $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                            'success','User retrieved successfully.',
                            \TYPO3\CMS\Core\Messaging\FlashMessage::OK, true
                        );
                        $out = GeneralUtility::makeInstance(ListRenderer ::class)
                            ->render([$message]);
                        echo $out;
//                            $flashMessageService = $this->objectManager->get(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
//                            $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
//                            $messageQueue->addMessage($message);
//                            $out = GeneralUtility::makeInstance(FlashMessageRendererResolver::class)
//                                ->resolve()
//                                ->render($message);
//                            \TYPO3\CMS\Core\Messaging\FlashMessageQueue::renderFlashMessages();
//                            $this->view->assign('flashyboi', [
//                                'header' => 'bad pwd',
//                                'content' => 'asdas',
//                                'priority' => 'error',
//                            ]);
                        //var_dump("2");exit;
                        //alert("User retrieved successfully");
//                            CD::save_customer($key_content);
//                            Flash::success("User retrieved successfully.");
                    }
                    else{
//                            $this->addFlashMessage(
//                                'Incorrect password entered on existing account.',
//                                $messageTitle = 'Error',
//                                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR,
//                                $storeInSession = TRUE
//                            );

                        $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                            'Error','Incorrect password entered on existing account.',
                            \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR, true
                        );
                        $out = GeneralUtility::makeInstance(ListRenderer ::class)
                            ->render([$message]);
                        echo $out;
//                            $this->addErrorFlashMessage();
//                            $flashMessageService = $this->objectManager->get(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
//                            $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
//                            $messageQueue->addMessage($message);
                        //var_dump($messageQueue);
//                            $this->render([$messageQueue]);
                        //var_dump("2");exit;
                        //  alert("Incorrect password entered on existing account.");
                        //Flash::error("Incorrect password entered on existing account.");
                    }
                }
                elseif (strcasecmp($create_content['status'], 'SUCCESS')==0){
                    // $this->update_cust('cust_email',$email);
                    $this->save_customer($create_content,$email);
                    $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                        'success','Thank you for registering with miniOrange.',
                        \TYPO3\CMS\Core\Messaging\FlashMessage::OK, true
                    );
                    $out = GeneralUtility::makeInstance(ListRenderer ::class)
                        ->render([$message]);
                    echo $out;
//                        $flashMessageService = $this->objectManager->get(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
//                        $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
//                        $messageQueue->addMessage($message);
//                        $out = GeneralUtility::makeInstance(FlashMessageRendererResolver::class)
//                            ->resolve()
//                            ->render($message);
//                        CD::save_customer($create_content);
//                        Flash::success("Thank you for registering with miniOrange.");
                    //  alert("Thank you for registering with miniOrange.");
                }
            }
            elseif (strcasecmp($check_content['status'],'SUCCESS')==0){
                $key_content = json_decode($customer->get_customer_key($email,$password), true);
                if(isset($key_content['id'])){
                    //$this->update_cust('cust_email',$email);
                    $this->save_customer($key_content,$email);
                    $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                        'success','User retrieved successfully.',
                        \TYPO3\CMS\Core\Messaging\FlashMessage::OK, true
                    );
                    $out = GeneralUtility::makeInstance(ListRenderer ::class)
                        ->render([$message]);
                    echo $out;
//                        $flashMessageService = $this->objectManager->get(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
//                        $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
//                        $messageQueue->addMessage($message);
//                        $out = GeneralUtility::makeInstance(FlashMessageRendererResolver::class)
//                            ->resolve()
//                            ->render($message);
//                        CD::save_customer($key_content);
//                        Flash::success("User retrieved successfully.");
                    // alert("User retrieved successfully");
                }
                else{
                    $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                        'Error','Incorrect password entered on existing account.',
                        \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR, true
                    );
                    $out = GeneralUtility::makeInstance(ListRenderer ::class)
                        ->render([$message]);
                    echo $out;
//                        $flashMessageService = $this->objectManager->get(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
//                        $messageQueue = $flashMessageService->getMessageQueueByIdentifier();
//                        $messageQueue->addMessage($message);
//                        $messageQueue->enqueue($message);
//                        $messageQueue->renderFlashMessages();
//                        var_dump($messageQueue);
////                        $out = GeneralUtility::makeInstance(FlashMessageRendererResolver::class)
//                            ->resolve()
//                            ->render();
                    //Flash::error("Incorrect password entered on existing account.");
                    //  alert("Incorrect password entered on existing account.");
                }
            }
        }

        //return Redirect::to(Backend::url('miniorange/samlsp/supportcontroller/update/1'));
    }
    public function save_customer($content,$email){

        $this->update_cust('cust_key',$content['id']);
        $this->update_cust('cust_api_key',$content['apiKey']);
        $this->update_cust('cust_token',$content['token']);
        $this->update_cust('cust_reg_status', 'logged');
        $this->update_cust('cust_email',$email);
    }
    public function support()
    {
        if ( ! $this->mo_saml_is_curl_installed() ) {
            $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                'Error','ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Query submit failed.',
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR, true
            );
            $out = GeneralUtility::makeInstance(ListRenderer ::class)
                ->render([$message]);
            echo $out;
            return;
        }

        // Contact Us query
        $email    = $_POST['mo_saml_contact_us_email'];
        $phone    = $_POST['mo_saml_contact_us_phone'];
        $query    = $_POST['mo_saml_contact_us_query'];
        $customer = new CustomerSaml();
        if ( $this->mo_saml_check_empty_or_null( $email ) || $this->mo_saml_check_empty_or_null( $query ) ) {
            $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                'SUCCESS','User retrieved successfully.',
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK, true
            );
            $out = GeneralUtility::makeInstance(ListRenderer ::class)
                ->render([$message]);
            echo $out;
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                'ERROR','Please enter a valid Email address. ',
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR, true
            );
            $out = GeneralUtility::makeInstance(ListRenderer ::class)
                ->render([$message]);
            echo $out;

        } else {
            $submitted = json_decode($customer->submit_contact( $email, $phone, $query ), true);
            if ( $submitted['status'] == 'ERROR' ) {
                $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                    'ERROR','could not send query. Please try again later or mail us at info@miniorange.com',
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR, true
                );
                $out = GeneralUtility::makeInstance(ListRenderer ::class)
                    ->render([$message]);
                echo $out;

            }
        }

    }
    public function save($column,$value,$table)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $affectedRows = $queryBuilder->insert($table)->values([
            $column => $value,
        ])->execute();
    }
    public function fetch_cust($var)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('registeration');
        $variable = $queryBuilder->select($var)->from('registeration')->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        return $variable;
    }
    public function update_cust($column,$value)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('registeration');
        $queryBuilder->update('registeration')->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->set($column, $value)->execute();
    }
    /**
     * @param $var
     */
    public function fetch($var)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $variable = $queryBuilder->select($var)->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        return $variable;
    }
    public function mo_saml_check_empty_or_null( $value ) {
        if( ! isset( $value ) || empty( $value ) ) {
            return true;
        }
        return false;
    }

    /**
     * @param $postArray
     */
    public function defaultSettings($postArray)
    {
        //        $this->site_base_url = $postArray['site_base_url'];
        ////        $this->acs_url = $postArray['acs_url'];
        ////        $this->sp_entity_id = $postArray['sp_entity_id'];
        ////        $this->slo_url = $postArray['slo_url'];
        ////        $this->fesaml = $postArray['fesaml'];
        ////        $this->response = $postArray['response'];
        $this->spobject = json_encode($postArray);
                if($this->site_base_url != null && $this->sp_entity_id && $this->acs_url) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
                    $affectedRows = $queryBuilder->insert('saml')->values([
                        'fesaml' => $this->fesaml,
                        'response' => $this->response,
                        'site_base_url' =>  $this->site_base_url,
                        'sp_entity_id' => $this->sp_entity_id,
                        'acs_url' => $this->acs_url,
                        'spobject' => $this->spobject
                    ])->execute();
                }
                else {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
                    $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->set('sp_entity_id', $postArray['sp_entity_id'])->execute();
                    $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->set('site_base_url', $postArray['site_base_url'])->execute();
                    $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->set('acs_url', $postArray['acs_url'])->execute();
                    $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->set('fesaml', $postArray['fesaml'])->execute();
                    $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->set('response', $postArray['response'])->execute();
                    $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->set('spobject', $this->spobject)->execute();
                }
    }

    /**
     * @param $creds
     */
    public function storeToDatabase($creds)
    {
        //        var_dump($creds);
        //        $creds['sp_entity_id'] = $this->sp_entity_id;
        //        $creds['acs_url'] = $this->acs_url;
        //        $creds['site_base_url'] = $this->site_base_url;
        //        $creds['slo_url'] = $this->slo_url;
        //        var_dump($creds);exit;
        $this->myjson = json_encode($creds);
//        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
//        $idp_name = $queryBuilder->select('idp_name')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        if ($this->fetch('uid') == null) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
            $affectedRows = $queryBuilder->insert('saml')->values([
                'idp_name' => $creds['idp_name'],
                'idp_entity_id' => $creds['idp_entity_id'],
                'saml_login_url' => $creds['saml_login_url'],
                'saml_logout_url' => $creds['saml_logout_url'],
                'x509_certificate' => $creds['x509_certificate'],
                'force_authn' => $creds['force_authn'],
                'login_binding_type' => $creds['login_binding_type'],
                'object' => $this->myjson
            ])->execute();
        } else {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->set('idp_name', $creds['idp_name'])->execute();
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->set('idp_entity_id', $creds['idp_entity_id'])->execute();
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->set('saml_login_url', $creds['saml_login_url'])->execute();
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->set('x509_certificate', $creds['x509_certificate'])->execute();
            //            $queryBuilder
            //                ->update('saml')
            //                ->where(
            //                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
            //                )
            //                ->set('site_base_url', $creds['site_base_url']
            //                )
            //                ->execute();
            //            $queryBuilder
            //                ->update('saml')
            //                ->where(
            //                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
            //                )
            //                ->set('sp_entity_id', $creds['sp_entity_id']
            //                )
            //                ->execute();
            //            $queryBuilder
            //                ->update('saml')
            //                ->where(
            //                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
            //                )
            //                ->set('acs_url', $creds['acs_url']
            //                )
            //                ->execute();
            //            $queryBuilder
            //                ->update('saml')
            //                ->where(
            //                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT))
            //                )
            //                ->set('slo_url', $creds['slo_url']
            //                )
            //                ->execute();
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->set('object', $this->myjson)->execute();
        }
    }
}
