<?php

echo '
<!-- Essential javascripts for application to work-->
<script src="includes/js/jquery-3.2.1.min.js"></script>
<script src="includes/js/popper.min.js"></script>
<script src="includes/js/bootstrap.min.js"></script>
<script src="includes/js/main.js"></script>
<!-- The javascript plugin to display page loading on top-->
<script src="includes/js/plugins/pace.min.js"></script>
';

if(!class_exists("DB")){
    require_once dirname(__FILE__) . '/helper/DB.php';
}

if(!class_exists("CustomerSaml")){
    require_once dirname(__FILE__) . '/classes/CustomerSaml.php';
}

if(!class_exists("AESEncryption")) {
	require_once dirname(__FILE__) . '/helper/lib/encryption.php';
}

DB::update_option('mo_saml_host_name', 'https://auth.miniorange.com');

if(isset($_POST['option']) && $_POST['option'] == 'mo_saml_contact_us'){
    $email    = $_POST['contact_us_email'];
    $phone    = $_POST['contact_us_phone'];
    $query    = $_POST['contact_us_query'];

    if ( mo_saml_check_empty_or_null( $email ) || mo_saml_check_empty_or_null( $query ) ) {
        DB::update_option( 'mo_saml_message', 'Please fill up Email and Query fields to submit your query.' );
        mo_saml_show_error_message();
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        DB::update_option( 'mo_saml_message', 'Please enter a valid email address.' );
        mo_saml_show_error_message();
    } else {
        $submited = $customer->submit_contact_us( $email, $phone, $query );
        if ( $submited == false ) {
            DB::update_option( 'mo_saml_message', 'Your query could not be submitted. Please try again.' );
            mo_saml_show_error_message();
        } else {
            DB::update_option( 'mo_saml_message', 'Thanks for getting in touch! We shall get back to you shortly.' );
            mo_saml_show_success_message();
        }
    }

    
}

if ( isset( $_POST['option'] ) and $_POST['option'] == "mo_saml_register_customer" ) {
    mo_register_action();
}

if ( isset( $_POST['option'] ) and $_POST['option'] == "mo_saml_goto_login" ) {
    DB::delete_option( 'mo_saml_new_registration' );
    DB::update_option( 'mo_saml_verify_customer', 'true' );
}

if( isset($_POST['option']) and $_POST['option'] == "change_miniorange"){
    mo_saml_remove_account();
    DB::update_option('mo_saml_guest_enabled',true);
    DB::update_option( 'mo_saml_message', 'Logged out of miniOrange account' );
    mo_saml_show_success_message();
    return;
}

if ( isset( $_POST['option'] ) and $_POST['option'] == "mo_saml_go_back" ) {
    DB::update_option( 'mo_saml_registration_status', '' );
    DB::update_option( 'mo_saml_verify_customer', '' );
    DB::delete_option( 'mo_saml_new_registration' );
    DB::delete_option( 'mo_saml_admin_email' );
    DB::delete_option( 'mo_saml_admin_phone' );
}

if ( isset( $_POST['option'] ) and $_POST['option'] == "mo_saml_verify_customer" ) {    //register the admin to miniOrange
    
    if ( ! mo_saml_is_curl_installed() ) {
        DB::update_option( 'mo_saml_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Login failed.' );
        mo_saml_show_error_message();

        return;
    }

    $email    = '';
    $password = '';
    if ( mo_saml_check_empty_or_null( $_POST['email'] ) || mo_saml_check_empty_or_null( $_POST['password'] ) ) {
        DB::update_option( 'mo_saml_message', 'All the fields are required. Please enter valid entries.' );
        mo_saml_show_error_message();

        return;
    } else if(checkPasswordpattern(strip_tags($_POST['password']))){
        DB::update_option( 'mo_saml_message', 'Minimum 6 characters should be present. Maximum 15 characters should be present. Only following symbols (!@#.$%^&*-_) should be present.' );
        mo_saml_show_error_message();
        return;
    }else {
        $email    = $_POST['email'];
        $password = stripslashes( strip_tags($_POST['password'] ));
    }

    DB::update_option( 'mo_saml_admin_email', $email );
    DB::update_option( 'mo_saml_admin_password', $password );
    $customer    = new CustomerSaml();
    $content     = $customer->get_customer_key();
    $customerKey = json_decode( $content, true );
    if ( json_last_error() == JSON_ERROR_NONE ) {
        DB::update_option( 'mo_saml_admin_customer_key', $customerKey['id'] );
        DB::update_option( 'mo_saml_admin_api_key', $customerKey['apiKey'] );
        DB::update_option( 'mo_saml_customer_token', $customerKey['token'] );
        $certificate = DB::get_option( 'saml_x509_certificate' );
        if ( empty( $certificate ) ) {
            DB::update_option( 'mo_saml_free_version', 1 );
        }
        DB::update_option( 'mo_saml_admin_password', '' );
        DB::update_option( 'mo_saml_message', 'Customer retrieved successfully' );
        DB::update_option( 'mo_saml_registration_status', 'Existing User' );
        DB::delete_option( 'mo_saml_verify_customer' );
        mo_saml_show_success_message();
        
    } else {
        DB::update_option( 'mo_saml_message', 'Invalid username or password. Please try again.' );
        mo_saml_show_error_message();
    }
    DB::update_option( 'mo_saml_admin_password', '' );
}

if(isset($_POST['option']) && $_POST['option'] == 'mo_saml_verify_license'){

    if( mo_saml_check_empty_or_null( $_POST['saml_license_key'] ) ) {
        DB::update_option( 'mo_saml_message', 'All the fields are required. Please enter valid license key.');
        mo_saml_show_error_message();
        return;
    }

    $code = trim($_POST['saml_license_key']);
    $customer = new CustomerSaml();
    $content = json_decode($customer->check_customer_ln(), true);
    if(strcasecmp($content['status'], 'SUCCESS') == 0){
        $content = json_decode($customer->mo_saml_vl($code,false),true);
        DB::update_option('vl_check_t',time());
        if(strcasecmp($content['status'], 'SUCCESS') == 0){
            $key = DB::get_option('mo_saml_customer_token');
            DB::update_option('sml_lk',AESEncryption::encrypt_data($code,$key));
            DB::update_option( 'mo_saml_message','Your license is verified. You can now configure the connector.');
            $key = DB::get_option('mo_saml_customer_token');
            DB::update_option('site_ck_l',AESEncryption::encrypt_data("true",$key));
            DB::update_option('t_site_status',AESEncryption::encrypt_data("false",$key));
            mo_saml_show_success_message();
        }else if(strcasecmp($content['status'], 'FAILED') == 0){
            if(strcasecmp($content['message'], 'Code has Expired') == 0){
                DB::update_option( 'mo_saml_message','License key you have entered has already been used. Please enter a key which has not been used before on any other instance or if you have exausted all your keys then buy more.');
            }else{
                DB::update_option( 'mo_saml_message','You have entered an invalid license key. Please enter a valid license key.');
            }
            mo_saml_show_error_message();
        }else{
            DB::update_option( 'mo_saml_message','An error occured while processing your request. Please Try again.');
            mo_saml_show_error_message();
        }
    }else {
        $key = DB::get_option('mo_saml_customer_token');
        DB::update_option('site_ck_l',AESEncryption::encrypt_data("false",$key));
        // $url = add_query_arg( array('tab' => 'licensing'), $_SERVER['REQUEST_URI'] );
        DB::update_option( 'mo_saml_message','You have not upgraded yet. ');
        mo_saml_show_error_message();
    }

}

if ( isset( $_POST['option'] ) and $_POST['option'] == "mo_saml_contact_us_query_option" ) {

    if ( ! mo_saml_is_curl_installed() ) {
        DB::update_option( 'mo_saml_message', 'ERROR: <a href="http://php.net/manual/en/curl.installation.php" target="_blank">PHP cURL extension</a> is not installed or disabled. Query submit failed.' );
        mo_saml_show_error_message();

        return;
    }

    // Contact Us query
    $email    = $_POST['mo_saml_contact_us_email'];
    $phone    = $_POST['mo_saml_contact_us_phone'];
    $query    = $_POST['mo_saml_contact_us_query'];
    $customer = new CustomerSaml();
    if ( mo_saml_check_empty_or_null( $email ) || mo_saml_check_empty_or_null( $query ) ) {
        DB::update_option( 'mo_saml_message', 'Please fill up Email and Query fields to submit your query.' );
        mo_saml_show_error_message();
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        DB::update_option( 'mo_saml_message', 'Please enter a valid email address.' );
        mo_saml_show_error_message();
    } else {
        $submited = $customer->submit_contact_us( $email, $phone, $query );
        if ( $submited == false ) {
            DB::update_option( 'mo_saml_message', 'Your query could not be submitted. Please try again.' );
            mo_saml_show_error_message();
        } else {
            DB::update_option( 'mo_saml_message', 'Thanks for getting in touch! We shall get back to you shortly.' );
            mo_saml_show_success_message();
        }
    }
}

if(isset($_POST['option']) && $_POST['option'] == 'save_connector_settings'){
    $saml_identity_name = '';
    $idp_entity_id = '';
    $saml_login_url = '';
    $saml_login_binding_type = '';
    $saml_logout_url = '';
    $saml_x509_certificate = '';
    $force_authentication = '';

    $sp_base_url = '';
    $sp_entity_id = '';
    $acs_url = '';
    $single_logout_url = '';

    if( mo_saml_check_empty_or_null( $_POST['idp_name'] ) || mo_saml_check_empty_or_null( $_POST['saml_login_url'] ) || mo_saml_check_empty_or_null( $_POST['idp_entity_id'])  ) {
        DB::update_option( 'mo_saml_message', 'All the fields are required. Please enter valid entries.');
        mo_saml_show_error_message();
        return;
    } else if(!preg_match("/^\w*$/", $_POST['idp_name'])) {
        DB::update_option( 'mo_saml_message', 'Please match the requested format for Identity Provider Name. Only alphabets, numbers and underscore is allowed.');
        mo_saml_show_error_message();
        return;
    } else{
        $saml_identity_name = trim( $_POST['idp_name'] );
        $saml_login_url = trim( $_POST['saml_login_url'] );
        if(array_key_exists('login_binding_type',$_POST))
            $saml_login_binding_type = $_POST['login_binding_type'];

        if(array_key_exists('saml_logout_url', $_POST)) {
            $saml_logout_url = trim( $_POST['saml_logout_url'] );
        }
        $idp_entity_id = trim( $_POST['idp_entity_id'] );
        $saml_x509_certificate = sanitize_certificate($_POST['x509_certificate']);
        if(isset($_POST['force_authn']) && !empty($_POST['force_authn'])){
            $force_authentication = true;
        } else {
            $force_authentication = false;
        }

        $sp_base_url = trim($_POST['site_base_url']);
        $sp_entity_id = trim($_POST['sp_entity_id']);
        $acs_url = trim($_POST['acs_url']);
        $single_logout_url = trim($_POST['slo_url']);

        DB::update_option('saml_identity_name', $saml_identity_name);
        DB::update_option('idp_entity_id', $idp_entity_id);
        DB::update_option('saml_login_url', $saml_login_url);
        DB::update_option('saml_login_binding_type', $saml_login_binding_type);
        DB::update_option('saml_logout_url', $saml_logout_url);
        DB::update_option('saml_x509_certificate', $saml_x509_certificate);
        DB::update_option('force_authentication', $force_authentication);
        DB::update_option('sp_base_url', $sp_base_url);    
        DB::update_option('sp_entity_id', $sp_entity_id);
        DB::update_option('acs_url', $acs_url);
        DB::update_option('single_logout_url', $single_logout_url);

        DB::update_option('mo_saml_message', 'Settings saved successfully.');
        mo_saml_show_success_message();

        if(empty($saml_x509_certificate)){
            DB::update_option("mo_saml_message",'Invalid Certificate:Please provide a certificate');
            mo_saml_show_error_message();
        }

        $saml_x509_certificate = sanitize_certificate( $saml_x509_certificate );
        if(!@openssl_x509_read($saml_x509_certificate)){
            DB::update_option('mo_saml_message', 'Invalid certificate: Please provide a valid certificate.');
            mo_saml_show_error_message();
            DB::delete_option('saml_x509_certificate');
        }
    }
}

if(isset($_POST['option']) && $_POST['option'] == 'attribute_mapping'){
    if(isset($_POST['saml_am_email']) && !empty($_POST['saml_am_email'])){
        DB::update_option('saml_am_email', $_POST['saml_am_email']);
      } else {
        DB::update_option('saml_am_email', 'NameID');
      }
      if(isset($_POST['saml_am_username']) && !empty($_POST['saml_am_username'])){
        DB::update_option('saml_am_username', $_POST['saml_am_username']);
      } else {
        DB::update_option('saml_am_username', 'NameID');
      }
      if(isset($_POST['attribute_name']) && isset($_POST['attribute_value'])){
        $key = $_POST['attribute_name'];
        $value = $_POST['attribute_value'];
        $custom_attrs = array_combine($key, $value);
        $custom_attrs = array_filter($custom_attrs);
        DB::update_option('mo_saml_custom_attrs_mapping', $custom_attrs);
      }
      DB::update_option('mo_saml_message', 'Attribute Mapping details saved successfully');
      mo_saml_show_success_message();
}

if(isset($_POST['option']) && $_POST['option'] == 'save_endpoint_url'){
    if(isset($_POST['application_url'])){
        DB::update_option('application_url', $_POST['application_url']);
    }
    if(isset($_POST['site_logout_url'])){
        DB::update_option('site_logout_url', $_POST['site_logout_url']);
    }
    DB::update_option('mo_saml_message', 'Endpoint URLs saved successfully.');
    mo_saml_show_success_message();
}

function mo_register_action(){

    // $user = wp_get_current_user();
    $email = $_POST['email'];
    $password = stripslashes($_POST['password']);
    $confirmPassword = stripslashes($_POST['confirmPassword']);

    DB::update_option( 'mo_saml_admin_email', $email );
    if ( strcmp( $password, $confirmPassword ) == 0 ) {
        DB::update_option( 'mo_saml_admin_password', $password );
        $customer = new CustomerSaml();
        $content  = json_decode( $customer->check_customer(), true );
        if ( strcasecmp( $content['status'], 'CUSTOMER_NOT_FOUND' ) == 0 ) {

            $response=create_customer();
        } else {
            $response=get_current_customer();


        }
        DB::update_option( 'mo_saml_message', 'Logged in as Guest.' );
        mo_saml_show_success_message();

    } else {
       $response['status'] = "not_match";
       DB::update_option('mo_saml_message', 'Passwords do not match.');
       mo_saml_show_error_message();
    }
    

    // header( "Content-Type: application/json" );
    // echo json_encode($response);

    //Don't forget to always exit in the ajax function.
    // exit();

}

function create_customer() {
    $customer    = new CustomerSaml();
    $customerKey = json_decode( $customer->create_customer(), true );
    $response = array();
    //print_r($customerKey);
    if ( strcasecmp( $customerKey['status'], 'CUSTOMER_USERNAME_ALREADY_EXISTS' ) == 0 ) {
        $api_response = get_current_customer();
        //print_r($api_response);exit;
        if($api_response){
            $response['status'] = "success";
        }
        else
            $response['status'] = "error";

    } else if ( strcasecmp( $customerKey['status'], 'SUCCESS' ) == 0 ) {
        DB::update_option( 'mo_saml_admin_customer_key', $customerKey['id'] );
        DB::update_option( 'mo_saml_admin_api_key', $customerKey['apiKey'] );
        DB::update_option( 'mo_saml_customer_token', $customerKey['token'] );
        DB::update_option( 'mo_saml_free_version', 1 );
        DB::update_option( 'mo_saml_admin_password', '' );
        DB::update_option( 'mo_saml_message', 'Thank you for registering with miniorange.' );
        DB::update_option( 'mo_saml_registration_status', '' );
        DB::delete_option( 'mo_saml_verify_customer' );
        DB::delete_option( 'mo_saml_new_registration' );
        $response['status']="success";
        return $response;
    }

    DB::update_option( 'mo_saml_admin_password', '' );
    return $response;
}

function get_current_customer() {
    $customer    = new CustomerSaml();
    $content     = $customer->get_customer_key();

    $customerKey = json_decode( $content, true );

    $response = array();
    if ( json_last_error() == JSON_ERROR_NONE ) {
        DB::update_option( 'mo_saml_admin_customer_key', $customerKey['id'] );
        DB::update_option( 'mo_saml_admin_api_key', $customerKey['apiKey'] );
        DB::update_option( 'mo_saml_customer_token', $customerKey['token'] );
        DB::update_option( 'mo_saml_admin_password', '' );
        $certificate = DB::get_option( 'saml_x509_certificate' );
        if ( empty( $certificate ) ) {
            DB::update_option( 'mo_saml_free_version', 1 );
        }

        DB::delete_option( 'mo_saml_verify_customer' );
        DB::delete_option( 'mo_saml_new_registration' );
        $response['status'] = "success";
        return $response;
    } else {

        DB::update_option( 'mo_saml_message', 'You already have an account with miniOrange. Please enter a valid password.' );
        mo_saml_show_error_message();
        //update_option( 'mo_saml_verify_customer', 'true' );
        //delete_option( 'mo_saml_new_registration' );
        $response['status'] = "error";
        return $response;
    }
}

function mo_saml_show_success_message(){
    echo '<script>
    var message = document.getElementById("saml_message");
    message.classList.add("success-message");
    message.innerText = "' . DB::get_option('mo_saml_message') . '"
    </script>';

    // echo '<p class="success-message">' . DB::get_option('mo_saml_message') . '</p>';
}

function mo_saml_show_error_message(){
    echo '<script>
    var message = document.getElementById("saml_message");
    message.classList.add("error-message");
    message.innerText = "' . DB::get_option('mo_saml_message') . '"
    </script>';

    // echo '<p class="error-message">' . DB::get_option('mo_saml_message') . '</p>';
}

function mo_saml_check_empty_or_null( $value ) {
    if( ! isset( $value ) || empty( $value ) ) {
        return true;
    }
    return false;
}

function mo_saml_is_customer_registered() {
	$email 			= DB::get_option('mo_saml_admin_email');
	$customerKey 	= DB::get_option('mo_saml_admin_customer_key');
	if( ! $email || ! $customerKey || ! is_numeric( trim( $customerKey ) ) ) {
		return 0;
	} else {
		return 1;
	}
}

function mo_saml_is_customer_license_verified() {
	$key = DB::get_option('mo_saml_customer_token');
	// $isTrialActive = AESEncryption::decrypt_data(get_option('t_site_status'),$key);
	$licenseKey = DB::get_option('sml_lk');
	$email 		= DB::get_option('mo_saml_admin_email');
	$customerKey = DB::get_option('mo_saml_admin_customer_key');
	if( !$licenseKey  || ! $email || ! $customerKey || ! is_numeric( trim( $customerKey ) ) ){
		return 0;
	}else {
		return 1;
	}
}
/*
function mo_saml_show_registration_page(){
    ?>
    
    <form name="f" method="post" action="">
                <input type="hidden" name="option" value="mo_saml_register_customer"/>
                <div class="mo_saml_table_layout" id="registration_div">
                    <h4>Register with miniOrange</h4>
                    <br/>
                    <h6>Why should I register?</h6>

                    <div style="background: aliceblue; padding: 10px 10px 10px 10px; border-radius: 10px;">
                    You should register so that in case you need help, we can help you with step by step
                            instructions. We support all known IdPs - ADFS, Okta, Salesforce, Shibboleth,
                                SimpleSAMLphp, OpenAM, Centrify, Ping, RSA, IBM, Oracle, OneLogin, Bitium, WSO2 etc.
                                <b>You will also need a miniOrange account to upgrade to the premium version of the connector.</b> We do not store any information except the email that you will use to register with us.
                    </div>
                    <br/>
                    <div class="col-lg-8">
                    <table class="mo_saml_settings_table">
                    <tr>
                          <td><b><font color="#FF0000">*</font>Email:</b></td>
                          <td><input class="form-control" type="email" name="email"
                                      required placeholder="person@example.com"
                                      value="<?php echo ( DB::get_option( 'mo_saml_admin_email' ) == '' ) ? DB::get_option( 'admin_email' ) : DB::get_option( 'mo_saml_admin_email' ); ?>"/>
                          </td>
                      </tr>
                      <tr>&nbsp;</tr>
                      <tr>
                          <td><b><font color="#FF0000">*</font>Password:</b></td>
                          <td><input class="form-control" required type="password"
                                      name="password" placeholder="Choose your password (Min. length 6)"
                                      minlength="6" pattern="^[(\w)*(!@#$.%^&*-_)*]+$"
                                      title="Minimum 6 characters should be present. Maximum 15 characters should be present. Only following symbols (!@#.$%^&*) should be present."
                                      /></td>
                      </tr>
                      <tr>
                          <td><b><font color="#FF0000">*</font>Confirm Password:</b></td>
                          <td><input class="form-control" required type="password"
                                      name="confirmPassword" placeholder="Confirm your password"
                                      minlength="6" pattern="^[(\w)*(!@#$.%^&*-_)*]+$"
                                      title="Minimum 6 characters should be present. Maximum 15 characters should be present. Only following symbols (!@#.$%^&*) should be present."

                                      /></td>
                      </tr>
                      <tr>
                          <td>&nbsp;</td>
                          <td><br><input type="submit" name="submit" value="Register" id="register_action"
                                          class="btn btn-primary"/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                          <input type="button" name="mo_saml_goto_login" id="mo_saml_goto_login"
                                      value="Already have an account?" class="btn btn-primary"/>&nbsp;&nbsp;

                          </td>
                      </tr>
                  </table></div>
                </div>
                </div>
                </form>
                <form name="f1" method="post" action="" id="mo_saml_goto_login_form">
                <input type="hidden" name="option" value="mo_saml_goto_login"/>
                </form>
                <form name="f" method="post" action="" id="mo_saml_continue_guest">
                    <input type="hidden" name="option" value="mo_continue_as_guest"/>
                </form>

                <!-- <form name="f2" method="post" action="" id="mo_saml_register_action_form">
                  <input type="hidden" name="option" value="mo_saml_register_action"/>
                </form> -->
                
                <script>
                    jQuery("#mo_saml_goto_login").click(function () {
                        jQuery("#mo_saml_goto_login_form").submit();
                    });
                </script>
              </div>
              <?php
}
*/
function mo_saml_show_verify_password_page(){
    ?>
    <form name="f" method="post" action="">
    <input type="hidden" name="option" value="mo_saml_verify_customer"/>
    <div class="mo_saml_table_layout">
  <div id="toggle1" class="panel_toggle">
      <h3>Login with miniOrange</h3>
  </div>
  <div id="panel1">
      <p><b>Please enter your miniOrange email
              and password.<br/> <a target="_blank"
                                    href="https://auth.miniorange.com/moas/idp/resetpassword">Click
                  here if you forgot your password?</a></b></p>
      <br/>
      <div class="col-lg-8">
      <table class="mo_saml_settings_table">
          <tr>
              <td><b><font color="#FF0000">*</font>Email:</b></td>
              <td><input class="form-control" type="email" name="email"
                         required placeholder="person@example.com"
                         value="<?php echo DB::get_option( 'mo_saml_admin_email' );?>"/></td>
          </tr>
          <tr>
              <td><b><font color="#FF0000">*</font>Password:</b></td>
              <td><input class="form-control" required type="password"
                         name="password" placeholder="Enter your password"
                         minlength="6" pattern="^[(\w)*(!@#$.%^&*-_)*]+$"
                         title="Minimum 6 characters should be present. Maximum 15 characters should be present. Only following symbols (!@#.$%^&*) should be present."

                         /></td>
          </tr>
          <tr>
              <td>&nbsp;</td>
              <td>
                  <input type="submit" name="submit" value="Login"
                         class="btn btn-primary"/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                  <!-- <input type="button" name="mo_saml_goback" id="mo_saml_goback" value="Back"
                         class="btn btn-primary"/> -->
          </tr>
      </table>
      </div>
  </div>
</div>
  </form>

        <!-- <form name="f" method="post" action="" id="mo_saml_goback_form">
            <input type="hidden" name="option" value="mo_saml_go_back"/>
        </form> -->
        <form name="f" method="post" action="" id="mo_saml_forgotpassword_form">
            <input type="hidden" name="option" value="mo_saml_forgot_password_form_option"/>
        </form>
        <script>
            // jQuery("#mo_saml_goback").click(function () {
            // jQuery("#mo_saml_goback_form").submit();
            // });
            jQuery("a[href=\"#mo_saml_forgot_password_link\"]").click(function () {
            jQuery("#mo_saml_forgotpassword_form").submit();
            });
        </script>
        <?php
}

function mo_saml_show_customer_details(){
    ?>
     <div class="mo_saml_table_layout" >
                <h2>Thank you for registering with miniOrange.</h2>

                <table border="1"
                   style="background-color:#FFFFFF; border:1px solid #CCCCCC; border-collapse: collapse; padding:0px 0px 0px 10px; margin:2px; width:85%">
                <tr>
                    <td style="width:45%; padding: 10px;">miniOrange Account Email</td>
                    <td style="width:55%; padding: 10px;"><?php echo DB::get_option( 'mo_saml_admin_email' ); ?></td>
                </tr>
                <tr>
                    <td style="width:45%; padding: 10px;">Customer ID</td>
                    <td style="width:55%; padding: 10px;"><?php echo DB::get_option( 'mo_saml_admin_customer_key' ) ?></td>
                </tr>
                </table>
                <br /><br />

            <table>
            <tr>
            <td>
            <form name="f1" method="post" action="" id="mo_saml_goto_login_form">
                <input type="hidden" value="change_miniorange" name="option"/>
                <input type="submit" value="Change Email Address" class="btn btn-primary"/>
            </form>
            </td><td>
            <a href="#"><input type="button" class="btn btn-primary"  onclick="upgradeform('php_connector_premium_plan')" value="Upgrade to Premium"/></a>
            </td>
            </tr>
            </table>

            <br />

            <form style="display:none;" id="loginform"
                 action="<?php echo DB::get_option( 'mo_saml_host_name' ) . '/moas/login'; ?>"
                 target="_blank" method="post">
                <input type="email" name="username" value="<?php echo DB::get_option( 'mo_saml_admin_email' ); ?>"/>
                <input type="text" name="redirectUrl"
                    value="<?php echo DB::get_option( 'mo_saml_host_name' ) . '/moas/initializepayment'; ?>"/>
                <input type="text" name="requestOrigin" id="requestOrigin"/>
            </form>
            <script>
                function upgradeform(planType) {
                    jQuery('#requestOrigin').val(planType);
                    if(jQuery('#mo_customer_registered').val()==1)
                        jQuery('#loginform').submit();

                }
            </script>
            </div>
            <?php
}

function mo_saml_show_verify_license_page(){
    ?>
    <div class="mo_saml_table_layout" style="padding-bottom:50px;!important">
                <?php
					 
				if(!DB::get_option('sml_lk')){
                ?>
                    <h3>Verify License  [ <span style="font-size:13px;font-style:normal;"><a style="cursor:pointer;" onclick="getlicensekeysform()" >Click here to view your license key</a></span> ]</h3><hr>

                    <?php
                        echo '<form style="display:none;" id="loginform" action="' . DB::get_option( 'mo_saml_host_name' ) . '/moas/login"
                            target="_blank" method="post">
                            <input type="email" name="username" value="' . DB::get_option( 'mo_saml_admin_email' ) . '" />
                            <input type="text" name="redirectUrl" value="' . DB::get_option( 'mo_saml_host_name' ) . '/moas/viewlicensekeys" />
                            <input type="text" name="requestOrigin" value="wp_saml_sso_basic_plan"  />
                        </form>'; ?>

						<form name="f" method="post" action="">
						<input type="hidden" name="option" value="mo_saml_verify_license" />
                            <div class="form-group">
                                <label for="saml_license_key"><b><font color="#FF0000">*</font>Enter your license key to activate the connector:</b></label>
                                <input class="form-control" required type="text" style="margin-left:40px;width:300px;"
                                    name="saml_license_key" id="saml_license_key" placeholder="Enter your license key to activate the conenctor"
                                />
                            </div>

							<ol>
							<li>License key you have entered here is associated with this site instance. In future, if you are re-installing the connector or your site for any reason, you should logout of your miniOrange account from the connector and then delete the connector. So that you can resuse the same license key.</li><br>
							<li><b>This is not a developer's license.</b> Making any kind of change to the connector's code will delete all your configuration and make the connector unusable.</li>
                            <br>
                            <div class="animated-checkbox">
                                <label>
                                <input type="checkbox" name="license_conditions" id="license_conditions" required>
                                <span class="label-text"><strong>I have read the above two conditions and I want to activate the connector.</strong></span>
                            </label>
                            </div>
								<input type="submit" name="submit" value="Activate License" class="btn btn-primary"/>
                                <input type="button" name="mo_saml_goback" id="mo_saml_goback" value="Back"
                         class="btn btn-primary"/>
                    </form>
                    
                    
<?php
				 } ?>
			</div>

		<form name="f" method="post" action="" id="mo_saml_free_trial_form">
			<input type="hidden" name="option" value="mo_saml_free_trial"/>
		</form>
		<form name="f" method="post" action="" id="mo_saml_check_license">
			<input type="hidden" name="option" value="mo_saml_check_license"/>
        </form>
        <form name="f" method="post" action="" id="mo_saml_goback_form">
            <input type="hidden" name="option" value="mo_saml_go_back"/>
        </form>
		<script>
			jQuery("#mo_saml_free_trial_link").click(function(){
				jQuery("#mo_saml_free_trial_form").submit();
			});
			jQuery("a[href=\"#checklicense\"]").click(function(){
                jQuery("#mo_saml_check_license").submit();
            });
            jQuery("#mo_saml_goback").click(function () {
            jQuery("#mo_saml_goback_form").submit();
            });
		</script>
        <?php
}

function mo_saml_remove_account(){
    DB::delete_option( 'mo_saml_host_name' );
    DB::delete_option( 'mo_saml_new_registration' );
    DB::delete_option( 'mo_saml_admin_phone' );
    DB::delete_option( 'mo_saml_admin_password' );
    DB::delete_option( 'mo_saml_verify_customer' );
    DB::delete_option( 'mo_saml_admin_customer_key' );
    DB::delete_option( 'mo_saml_admin_api_key' );
    DB::delete_option( 'mo_saml_customer_token' );
    DB::delete_option('mo_saml_admin_email');
    DB::delete_option( 'mo_saml_message' );
    DB::delete_option( 'mo_saml_registration_status' );
    DB::delete_option( 'mo_saml_idp_config_complete' );
    DB::delete_option( 'mo_saml_transactionId' );
    DB::delete_option('vl_check_t');
    DB::delete_option('sml_lk');

}

function checkPasswordpattern($password){
    $pattern = '/^[(\w)*(\!\@\#\$\%\^\&\*\.\-\_)*]+$/';

    return !preg_match($pattern,$password);
}

function mo_saml_is_curl_installed() {
    if ( in_array( 'curl', get_loaded_extensions() ) ) {
        return 1;
    } else {
        return 0;
    }
}

function mo_saml_is_customer_registered_saml($check_guest=true) {

    $email       = DB::get_option( 'mo_saml_admin_email' );
    $customerKey = DB::get_option( 'mo_saml_admin_customer_key' );

    if(mo_saml_is_guest_enabled() && $check_guest)
        return 1;
    if ( ! $email || ! $customerKey || ! is_numeric( trim( $customerKey ) ) ) {
        return 0;
    } else {
        return 1;
    }
}

function mo_saml_is_guest_enabled(){
    $guest_enabled = DB::get_option('mo_saml_guest_enabled');
    return $guest_enabled;
}

function check_license(){
    $code = DB::get_option('sml_lk');
		if($code){
			$code  = AESEncryption::decrypt_data($code,$key);
			$customer = new CustomerSaml();
			$content = json_decode($customer->mo_saml_vl($code,true),true);
			if(strcasecmp($content['status'], 'SUCCESS') == 0){
                return true;
            } else {
                return false;
            }
		}
}

function site_check(){
	$status = false;
	$key = DB::get_option('mo_saml_customer_token');
	if(DB::get_option("site_ck_l")){
		if(AESEncryption::decrypt_data(DB::get_option('site_ck_l'),$key)=="true")
			$status = true;
	}
	if($status && !mo_saml_lk_multi_host()){
		$vl_check_t = DB::get_option('vl_check_t');
		if($vl_check_t){
			$vl_check_t = intval($vl_check_t);
			if(time()-$vl_check_t<3600*24*3)
				return $status;
		}
		$code = DB::get_option('sml_lk');
		if($code){
			$code  = AESEncryption::decrypt_data($code,$key);
			$customer = new CustomerSaml();
			$content = json_decode($customer->mo_saml_vl($code,true),true);
			if(strcasecmp($content['status'], 'SUCCESS') == 0){DB::delete_option('vl_check_s');}else{DB::update_option('vl_check_s',AESEncryption::encrypt_data("false",$key));}
		}
		DB::update_option('vl_check_t',time());
	}
	return $status;
}

function mo_saml_lk_multi_host(){
	$vl_check_s = DB::get_option('vl_check_s');
	$key = DB::get_option('mo_saml_customer_token');
	if($vl_check_s){
		$vl_check_s = AESEncryption::decrypt_data($vl_check_s,$key);
		if($vl_check_s=="false")
			return true;
	}
	return false;
}

function is_user_registered(){
    $str='';
    if((file_exists(dirname(__FILE__) . '\helper\data\credentials.json')))
      $str = file_get_contents(dirname(__FILE__) . '\helper\data\credentials.json');
    if(!empty($str)){
      $credentials_array = json_decode($str, true);
      if(!empty($credentials_array)){
        return true;
      } else 
        return false;
    }
    else
      return false;
        
  }

  function sanitize_certificate( $certificate ) {
    $certificate = trim($certificate);
    $certificate = preg_replace("/[\r\n]+/", "", $certificate);
    $certificate = str_replace( "-", "", $certificate );
    $certificate = str_replace( "BEGIN CERTIFICATE", "", $certificate );
    $certificate = str_replace( "END CERTIFICATE", "", $certificate );
    $certificate = str_replace( " ", "", $certificate );
    $certificate = chunk_split($certificate, 64, "\r\n");
    $certificate = "-----BEGIN CERTIFICATE-----\r\n" . $certificate . "-----END CERTIFICATE-----";
    return $certificate;
}


?>
