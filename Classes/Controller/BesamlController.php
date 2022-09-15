<?php

namespace Miniorange\MiniorangeSaml\Controller;

use Exception;
use Miniorange\Helper\Constants;
use PDO;
use DOMDocument;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\Renderer\ListRenderer;
use Miniorange\Helper\CustomerSaml;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Miniorange\Helper\Utilities;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateModuleController;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use Psr\Http\Message\ResponseFactoryInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use Miniorange\Helper\SAMLUtilities;
use Miniorange\Helper\IDPMetadataReader;
use Miniorange\Helper\IdentityProviders;
use Miniorange\Helper\SetRedirect;  


/**
 * BesamlController
 */
class BesamlController extends ActionController
{
    private $myjson = null;

    private $spobject = null;

    protected $fesaml = null;

    protected $response = null;

    private $tab = null;

    /**
     * @var TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

	/**
	 * @throws Exception
	 */
    public function requestAction()
    {

        //-------------Upload Metadata-----------------------

        if(isset($_POST['option']) and $_POST['option']=='upload_metadata_file')
        {
            Self::_handle_upload_metadata();
        }
        //----------- Download metadata---------------
        if(isset($_POST['option']) and $_POST['option']=='mosaml_metadata_download')
        {
            $value1 = $this->validateURL($_POST['site_base_url']);
            $value2 = $this->validateURL($_POST['acs_url']);
            $value3 = $this->validateURL($_POST['sp_entity_id']);

                if($value1 == 1 && $value2 == 1 && $value3 == 1)
                {
                    SAMLUtilities::mo_saml_miniorange_generate_metadata(true);
                }
                else{
                    Utilities::showErrorFlashMessage('Fill all the fields to download the metadata file');
                }

        }


        //---------------show Metadata-------------------------
        
        if(isset($_POST['option']) and $_POST['option']=='mosaml_metadata')
        {
            SAMLUtilities::mo_saml_miniorange_generate_metadata();
        }

//------------ IDENTITY PROVIDER SETTINGS---------------
        if(isset($_POST['option']) and $_POST['option'] == 'idp_settings'){

        	$value1 = $this->validateURL($_POST['saml_login_url']);
            $value2 = $this->validateURL($_POST['idp_entity_id']);
            $value3 = Utilities::check_certificate_format($_POST['x509_certificate']);
            
            if($value1 == 1 && $value2 == 1 && $value3 == 1)
            {
                $obj = new BesamlController();
                $obj->storeToDatabase($_POST);
				Utilities::showSuccessFlashMessage('IDP Setting saved successfully.');
            }else{
                if ($value3 == 0) {
                	  Utilities::showErrorFlashMessage('Incorrect Certificate Format');
                }else {
                	 Utilities::showErrorFlashMessage('Blank Field or Invalid input');
                }
            }
        }

//------------ VERIFY CUSTOMER---------------
        elseif ( isset( $_POST['option'] ) and $_POST['option'] == "mo_saml_verify_customer" ) {

			if($_POST['registered'] =='isChecked'){
                error_log("registered is checked. Registering User : ");
			    $this->account($_POST);
            }else{
			    if($_POST['password'] == $_POST['confirmPassword']){
                    $this->account($_POST);
			    }else{
                    Utilities::showErrorFlashMessage('Please enter same password in both password fields.');
                }
            }

        }

//------------ HANDLE LOG OUT ACTION---------------
            elseif(isset($_POST['option']) and $_POST['option']== 'logout'){
                    $this->remove_cust();
                    Utilities::showSuccessFlashMessage('Logged out successfully.');
                    $this->view->assign('status','not_logged');
            }

//------------ SERVICE PROVIDER SETTINGS---------------
            elseif(isset($_POST['option']) and $_POST['option'] == 'save_sp_settings') {
                $value1 = $this->validateURL($_POST['site_base_url']);
                $value2 = $this->validateURL($_POST['acs_url']);
                $value3 = $this->validateURL($_POST['sp_entity_id']);

                if($value1 == 1 && $value2 == 1 && $value3 == 1)
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


        //GROUP MAPPINGS
        elseif(isset($_POST['option']) and $_POST['option'] == 'group_mapping'){
            Utilities::updateTable(Constants::COLUMN_GROUP_DEFAULT, $_POST['defaultUserGroup'],Constants::TABLE_SAML);
            Utilities::showSuccessFlashMessage('Default Group saved successfully.');
        }

//------------ CHANGING TABS---------------
if(!empty($_POST['option'])){   
        if($_POST['option'] == 'save_sp_settings' )
        {
            $this->tab = "Service_Provider";
        }
        elseif ($_POST['option'] == 'attribute_mapping')
        {
            $this->tab = "Attribute_Mapping";
        }
        elseif ($_POST['option'] == 'group_mapping')
        {
            $this->tab = "Group_Mapping";
        }
        elseif ($_POST['option'] == 'get_premium')
        {
            $this->tab = "Premium";
        }
        else
        {
            $this->tab = "Identity_Provider";
        }
    }

        $this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $allUserGroups= $this->objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\FrontendUserGroupRepository')->findAll();
        $allUserGroups->getQuery()->getQuerySettings()->setRespectStoragePage(false);
        $this->view->assign('allUserGroups', $allUserGroups);
        $this->view->assign('defaultGroup',Utilities::fetchFromTable(Constants::COLUMN_GROUP_DEFAULT,Constants::TABLE_SAML));

//------------ LOADING SAVED SETTINGS OBJECTS TO BE USED IN VIEW---------------
        $this->view->assign('conf_idp', json_decode($this->fetch('object'), true));
        $this->view->assign('conf_sp', json_decode($this->fetch('spobject'), true));

//------------ LOADING VARIABLES TO BE USED IN VIEW---------------
//        if($this->fetch_cust(Constants::CUSTOMER_REGSTATUS) == 'logged'){
//					$this->view->assign('status','logged');
//					$this->view->assign('log', '');
//                    $this->view->assign('nolog', 'display:none');
//					$this->view->assign('email',$this->fetch_cust('cust_email'));
//					$this->view->assign('key',$this->fetch_cust('cust_key'));
//					$this->view->assign('token',$this->fetch_cust('cust_token'));
//					$this->view->assign('api_key',$this->fetch_cust('cust_api_key'));
//        }else{
//					$this->view->assign('log', 'disabled');
//                    $this->view->assign('nolog', 'display:block');
//					$this->view->assign('status','not_logged');
//        }

        $this->view->assign('tab', $this->tab);
        $this->view->assign('extPath', Utilities::getExtensionRelativePath());
     //   $this->cacheService->clearPageCache([$GLOBALS['TSFE']->id]);
     GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->flushCaches();

     return $this->responseFactory->createResponse()
     ->withAddedHeader('Content-Type', 'text/html; charset=utf-8')
     ->withBody($this->streamFactory->createStream($this->view->render()));

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

//        $this->update_saml_setting('idp_name',"");
//        $this->update_saml_setting('idp_entity_id',"");
//        $this->update_saml_setting('saml_login_url',"");
//		  $this->update_saml_setting('saml_logout_url',"");
//        $this->update_saml_setting('x509_certificate',"");
//        $this->update_saml_setting('login_binding_type',"");
//        $this->update_saml_setting('object',"");
    }

    public static function generic_update_query($database_name, $updatefieldsarray){
        $idp_obj = json_encode($updatefieldsarray);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        foreach ($updatefieldsarray as $key => $value)
        {
            //$queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set($key, $value)->execute();
            if($key == 'idp_entity_id')
            {
                $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set('idp_entity_id', $value)->execute();

            }
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set('object', $idp_obj)->execute();

            // echo ($key." : ");
            // echo Utilities::fetchFromTable($key,'saml');
        }
        //exit;
        // foreach ($updatefieldsarray as $key => $value)
        // echo $queryBuilder->select($key)->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        Utilities::showSuccessFlashMessage('IDP Setting saved successfully.');
    }

//    VALIDATE CERTIFICATE
    public function validate_cert($saml_x509_certificate)
    {

		$certificate = openssl_x509_parse ( $saml_x509_certificate);

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
               error_log("CUSTOMER_NOT_FOUND.. Creating ...");
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
                    'login_binding_type' => Constants::HTTP_REDIRECT,
                    'object' => $this->myjson,])
                ->execute();
            error_log("affected rows ".$affectedRows);
        }else {

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
            if(!empty($creds['idp_name']))
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))
                ->set('idp_name', $creds['idp_name'])->execute();
            if(!empty($creds['idp_entity_id']))
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))
                ->set('idp_entity_id', $creds['idp_entity_id'])->execute();
            if(!empty($creds['saml_login_url']))
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))
                ->set('saml_login_url', $creds['saml_login_url'])->execute();
            if(!empty($creds['saml_logout_url']))
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))
                ->set('saml_logout_url', $creds['saml_logout_url'])->execute();
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))
                ->set('login_binding_type', Constants::HTTP_REDIRECT)->execute();
            if(!empty($creds['x509_certificate']))
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))
                ->set('x509_certificate', $creds['x509_certificate'])->execute();
            $queryBuilder->update('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))
                ->set('object', $this->myjson)->execute();
        }
    }

    public static function _handle_upload_metadata() {
        $obj1= new BesamlController();
        if (isset($_FILES['metadata_file']) || isset($_POST['upload_url'])) {
            if (!empty($_FILES['metadata_file']['tmp_name'])) {
                $file = @file_get_contents($_FILES['metadata_file']['tmp_name']);
            } else {
                $url = filter_var($_POST['upload_url'], FILTER_SANITIZE_URL);
                $arrContextOptions = array(
                    "ssl" => array(
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                    ),
                );
                if (empty($url)) {
                    Utilities::showErrorFlashMessage('No Metadata File/URL Provided.');
                    return;
                } else {
                    $file = file_get_contents($url, false, stream_context_create($arrContextOptions));
                }
            }
            self::upload_metadata($file);
        } 
	}

    public static function upload_metadata($file) {
        $besaml=new BesamlController();
		$document = new DOMDocument();
        $document->loadXML($file);
        restore_error_handler();
        $first_child = $document->firstChild;
        if (!empty($first_child)) {
            $metadata = new IDPMetadataReader($document);
            $identity_providers = $metadata->getIdentityProviders();
            if (empty($identity_providers)) {
                Utilities::showErrorFlashMessage('Please provide valid metadata.');

                return;
            }
            foreach ($identity_providers as $key => $idp) {
                $saml_login_url = $idp->getLoginURL('HTTP-Redirect');
                $saml_issuer = $idp->getEntityID();
                $saml_x509_certificate = $idp->getSigningCertificate();
                $database_name = 'saml';
                $updatefieldsarray = array(
                    'idp_entity_id' => isset($saml_issuer) ? $saml_issuer : 0,
                    'saml_login_url' => isset($saml_login_url) ? $saml_login_url : 0,
                    'login_binding_type' => 'HttpRedirect',
                    'x509_certificate' => isset($saml_x509_certificate) ? $saml_x509_certificate[0] : 0,
                );

                self::generic_update_query($database_name, $updatefieldsarray);
                break;
            }
            return;
        } else {
            
            return;
        }
	}
}
