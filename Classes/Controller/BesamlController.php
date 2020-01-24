<?php

namespace Miniorange\MiniorangeSaml\Controller;

use Exception;
use Miniorange\MiniorangeSaml\Domain\Model\Besaml;
use PDO;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use Miniorange\classes\CustomerSaml;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Miniorange\helper\Utilities;
use Miniorange\helper\PluginSettings;

/***
 *
 * This file is part of the "etitle" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Miniorange <info@xecurify.com>
 *
 ***/

/**
 * BesamlController
 */
class BesamlController extends ActionController
{
    /**
     * besamlRepository
     * 
     * @var \Miniorange\MiniorangeSaml\Domain\Repository\BesamlRepository
     * @inject
     */
    public $besamlRepository = null;

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

    protected $tab = "";

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
     * @param Besaml $besaml
     * @return void
     */
    public function showAction(Besaml $besaml)
    {
        $this->view->assign('besaml', $besaml);
    }

	/**
	 * @throws Exception
	 */
    public function requestAction()
    {

        error_log("inside showAction : BeSamlController : ");
        error_log("REQUEST : ".$_POST['option']);

//------------ IDENTITY PROVIDER SETTINGS---------------
        if( !empty($_POST['idp_name']) and isset($_POST['idp_entity_id']) and isset($_POST['saml_login_url']) and isset($_POST['saml_logout_url'])){



        	error_log("Received IdP Settings - : \n Name :".$_POST['idp_name'].
                                                        " \n Entity ID :".$_POST['idp_entity_id'].
                                                        " \n SSO Url :".$_POST['saml_login_url']);

        	$value1 = $this->validateURL($_POST['saml_login_url']);
            $value2 = $this->validateURL($_POST['idp_entity_id']);
            $value3 = Utilities::check_certificate_format($_POST['x509_certificate']);
            $value4 = $this->validateURL($_POST['saml_logout_url']);
            error_log("Check_certificate_format: ".$value3);

            if($value1 == 1 && $value2 == 1 && $value3 == 1 && $value4 = 1)
            {
                $obj = new BesamlController();
                $obj->storeToDatabase($_POST);
				Utilities::showSuccessFlashMessage('IdP Setting saved successfully.');
            }else{
                if ($value3 == 0) {
                	  Utilities::showErrorFlashMessage('Incorrect Certificate Format');
                }else {
                	 Utilities::showErrorFlashMessage('Blank Field or Invalid input');
                }
            }
        }

//------------ HANDLING SUPPORT QUERY---------------
        if ( isset( $_POST['option'] ) and $_POST['option'] == "mo_saml_contact_us_query_option" ) {
			 error_log('Received support query.  ');
            $this->support();
        }

//------------ VERIFY CUSTOMER---------------
        if ( isset( $_POST['option'] ) and $_POST['option'] == "mo_saml_verify_customer" ) {
			error_log('Received verify customer request(login). ');

			if($_POST['registered'] =='isChecked'){
                $this->account($_POST);
                error_log("registered is checked");
            }else{
			    if($_POST['password'] == $_POST['confirmPassword']){
                    $this->account($_POST);
                    error_log("both passwords are equal.");
			    }else{
                    Utilities::showErrorFlashMessage('Please enter same password in both password fields.');
                    error_log("both passwords are not same.");
                }
            }

        }

//------------ HANDLE LOG OUT ACTION---------------
				if(isset($_POST['option'])){
//					error_log("inside option ");
					if ($_POST['option']== 'logout') {
						error_log('Received log out request.');
						$this->remove_cust();
						Utilities::showSuccessFlashMessage('Logged out successfully.');
					}
					$this->view->assign('status','not_logged');
				}

//------------ SERVICE PROVIDER SETTINGS---------------
				if ($_POST['site_base_url'] != null || $_POST['acs_url'] != null
						                                || $_POST['sp_entity_id'] != null
						                                || $_POST['slo_url'] != null) {

					  $value1 = $this->validateURL($_POST['site_base_url']);
						$value2 = $this->validateURL($_POST['acs_url']);
						$value3 = $this->validateURL($_POST['sp_entity_id']);
  					$value4 = $this->validateURL($_POST['slo_url']);

						//error_log(" Url Validation Values :".$value1.$value2.$value3);

						if($value1 == 1 && $value2 == 1 && $value3 == 1 && $value4 == 1)
						{
                            if($this->fetch('uid') == null){
                                $this->save('uid',1,'saml');
                            }
							$this->defaultSettings($_POST);
							Utilities::showSuccessFlashMessage('SP Setting saved successfully.');

						}else{
							  Utilities::showErrorFlashMessage('Incorrect Input');
						}
				}

//------------ CHANGING TABS---------------
        if($_POST['option'] == 'save_sp_settings' )
        {
            $this->tab = "Service_Provider";
        }
        elseif ($_POST['option'] == 'mo_saml_verify_customer')
        {
            $this->tab = "Account";

        }
        elseif ($_POST['option'] == 'save_connector_settings')
        {
            $this->tab = "Identity_Provider";
        }
        elseif ($_POST['option'] == 'attribute_mapping')
        {
            $this->tab = "Attribute_Mapping";

        }
        elseif ($_POST['option'] == 'mo_saml_contact_us_query_option')
        {
            $this->tab = "Support";

        }

//------------ LOADING SAVED SETTINGS OBJECTS TO BE USED IN VIEW---------------
        $this->view->assign('conf', json_decode($this->fetch('object'), true));
        $this->view->assign('conf2', json_decode($this->fetch('spobject')), true);
        $this->view->assign('conf1', json_decode($this->fetch('attrobject'), true));

//------------ LOADING VARIABLES TO BE USED IN VIEW---------------
        if($this->fetch_cust('cust_reg_status') == 'logged'){
					$this->view->assign('status','logged');
					$this->view->assign('log', '');
                    $this->view->assign('nolog', 'display:none');
					$this->view->assign('email',$this->fetch_cust('cust_email'));
					$this->view->assign('key',$this->fetch_cust('cust_key'));
					$this->view->assign('token',$this->fetch_cust('cust_token'));
					$this->view->assign('api_key',$this->fetch_cust('cust_api_key'));
        }else{
					$this->view->assign('log', 'disabled');
                    $this->view->assign('nolog', 'display:block');
					$this->view->assign('status','not_logged');
        }

        $this->view->assign('tab', $this->tab);
        $this->view->assign('extPath',Utilities::getExtensionRelativePath());

        $caches = new TypoScriptTemplateModuleController();
        $caches->clearCache();
        $this->cacheService->clearPageCache([$GLOBALS['TSFE']->id]);
    }

    public function save($column,$value,$table)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $affectedRows = $queryBuilder->insert($table)->values([ $column => $value, ])->execute();
    }

//  LOGOUT CUSTOMER
    public function remove_cust(){
        $this->update_cust('cust_key','');
        $this->update_cust('cust_api_key','');
        $this->update_cust('cust_token','');
        $this->update_cust('cust_reg_status', '');
        $this->update_cust('cust_email','');

        $this->update_saml_setting('idp_name',"");
        $this->update_saml_setting('idp_entity_id',"");
        $this->update_saml_setting('saml_login_url',"");
		$this->update_saml_setting('saml_logout_url',"");
        $this->update_saml_setting('x509_certificate',"");
        $this->update_saml_setting('login_binding_type',"");
        $this->update_saml_setting('object',"");
    }

//    VALIDATE CERTIFICATE
    public function validate_cert($saml_x509_certificate)
    {

    	  error_log("saml_certificate : ".print_r($saml_x509_certificate,true));

			  $certificate = openssl_x509_parse ( $saml_x509_certificate);

			  error_log("parsed certificate : ".print_r($certificate,true));

        foreach( $certificate as $key => $value ) {
            if ( empty( $value ) ) {
                unset( $saml_x509_certificate[ $key ] );
                return 0;
            } else {
                $saml_x509_certificate[ $key ] = $this->sanitize_certificate( $value );
                if ( ! @openssl_x509_read( $saml_x509_certificate[ $key ] ) ) {
                    return 0;
                }
            }
        }

        if ( empty( $saml_x509_certificate ) ) {
            return 0;
        }

        return 1;
    }

//    VALIDATE URLS
    public function validateURL($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return 1;
        } else {
            return 0;
        }
    }

    public function mo_saml_is_curl_installed() {
        if ( in_array( 'curl', get_loaded_extensions() ) ) {
            return 1;
        } else {
            return 0;
        }
    }

//   HANDLE LOGIN FORM
    public function account($post){
        $email = $post['email'];
        $password = $post['password'];
        $customer = new CustomerSaml();
        $customer->email = $email;
        $this->update_cust('cust_email', $email);
        $check_content = json_decode($customer->check_customer($email,$password), true);

        if($check_content['status'] == 'CUSTOMER_NOT_FOUND'){

        	   $customer = new CustomerSaml();

        	   $result = $customer->create_customer($email,$password);

        	   if($result['status']== 'SUCCESS' ){
							 $key_content = json_decode($customer->get_customer_key($email,$password), true);
							 if($key_content['status'] == 'SUCCESS'){
								 $this->save_customer($key_content,$email);
								 Utilities::showSuccessFlashMessage('User retrieved successfully.');
							 }else{
								 Utilities::showErrorFlashMessage('It seems like you have entered the incorrect password');
							 }
						 }

        }elseif ($check_content['status'] == 'SUCCESS'){
            $key_content = json_decode($customer->get_customer_key($email,$password), true);

            if($key_content['status'] == 'SUCCESS'){
                $this->save_customer($key_content,$email);
                Utilities::showSuccessFlashMessage('User retrieved successfully.');
            }
            else{
            	  Utilities::showErrorFlashMessage('It seems like you have entered the incorrect password');
            }
        }
    }

//  SAVE CUSTOMER
    public function save_customer($content,$email){
        $this->update_cust('cust_key',$content['id']);
        $this->update_cust('cust_api_key',$content['apiKey']);
        $this->update_cust('cust_token',$content['token']);
        $this->update_cust('cust_reg_status', 'logged');
        $this->update_cust('cust_email',$email);
    }

// FETCH CUSTOMER
    public function fetch_cust($col)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('customer');
        $variable = $queryBuilder->select($col)->from('customer')->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->execute()->fetchColumn(0);
        return $variable;
    }

// ---- UPDATE CUSTOMER Details
	  public function update_cust($column,$value)
    {
        if($this->fetch_cust('id') == null)
        {
            $this->insertValue();
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('customer');
        $queryBuilder->update('customer')->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set($column, $value)->execute();
    }

	// ---- UPDATE SAML Settings
	public function update_saml_setting($column, $value)
	{
		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
		$queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set($column, $value)->execute();
	}

  public function insertValue()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('customer');
        $affectedRows = $queryBuilder->insert('customer')->values([  'id' => '1' ])->execute();
    }

// --------------------SUPPORT QUERY---------------------
	public function support(){
        if(!$this->mo_saml_is_curl_installed() ) {
        	  Utilities::showErrorFlashMessage('ERROR: <a href="http://php.net/manual/en/curl.installation.php" 
                       target="_blank">PHP cURL extension</a> is not installed or disabled. Query submit failed.');
            return;
        }
        // Contact Us query
        $email    = $_POST['mo_saml_contact_us_email'];
        $phone    = $_POST['mo_saml_contact_us_phone'];
        $query    = $_POST['mo_saml_contact_us_query'];

        $customer = new CustomerSaml();

        if($this->mo_saml_check_empty_or_null( $email ) || $this->mo_saml_check_empty_or_null( $query ) ) {
          Utilities::showErrorFlashMessage('Please enter a valid Email address. ');
        }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        	  Utilities::showErrorFlashMessage('Please enter a valid Email address. ');
        }else {
            $submitted = json_decode($customer->submit_contact( $email, $phone, $query ), true);
					if ( $submitted['status'] == 'SUCCESS' ) {
						Utilities::showSuccessFlashMessage('Support query sent ! We will get in touch with you shortly.');
					}else{
            	      Utilities::showErrorFlashMessage('could not send query. Please try again later or mail us at info@miniorange.com');
					}
        }
    }

	/**
	 * @param $var
	 * @return bool|string
	 */
    public function fetch($var){
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $variable = $queryBuilder->select($var)->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->execute()->fetchColumn(0);
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
		$this->spobject = json_encode($postArray);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set('sp_entity_id', $postArray['sp_entity_id'])->execute();
        $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set('site_base_url', $postArray['site_base_url'])->execute();
        $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set('acs_url', $postArray['acs_url'])->execute();
		$queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set('slo_url', $postArray['slo_url'])->execute();
        $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set('fesaml', $postArray['fesaml'])->execute();
        $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set('response', $postArray['response'])->execute();
        $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set('spobject', $this->spobject)->execute();
    }

    /**
     * @param $creds
     */
    public function storeToDatabase($creds)
    {

        $this->myjson = json_encode($creds);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $uid = $queryBuilder->select('uid')->from('saml')
						                     ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))
						                     ->execute()->fetchColumn(0);

        error_log("If Previous_IdP configured : ".$uid);

        if ($uid == null) {
            error_log("No Previous_IdP found : ".$uid);
            $affectedRows = $queryBuilder
                ->insert('saml')
                ->values([
                    'uid' => $queryBuilder->createNamedParameter(1, PDO::PARAM_INT),
                    'idp_name' => $creds['idp_name'],
                    'idp_entity_id' => $creds['idp_entity_id'],
                    'saml_login_url' => $creds['saml_login_url'],
                    'saml_logout_url' => $creds['saml_logout_url'],
                    'x509_certificate' => $creds['x509_certificate'],
                    'force_authn' => $creds['force_authn'],
                    'login_binding_type' => $creds['login_binding_type'],
                    'object' => $this->myjson,])
                ->execute();
            error_log("affected rows ".$affectedRows);
        }else {

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))
                ->set('idp_name', $creds['idp_name'])->execute();
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))
                ->set('idp_entity_id', $creds['idp_entity_id'])->execute();
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))
                ->set('saml_login_url', $creds['saml_login_url'])->execute();
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))
                ->set('saml_logout_url', $creds['saml_logout_url'])->execute();
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))
                ->set('x509_certificate', $creds['x509_certificate'])->execute();
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))
                ->set('object', $this->myjson)->execute();
        }
    }
}
