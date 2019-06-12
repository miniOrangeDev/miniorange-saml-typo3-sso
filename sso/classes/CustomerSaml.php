<?php
namespace MiniOrange\Classes;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\Renderer\ListRenderer;
//if(!class_exists("DB")){
//    require_once dirname(__FILE__) . '/helper/DB.php';
//}
    class CustomerSaml{
        public $email;
        

        function create_customer($email,$password,$confirmPassword) {
		
            $url = 'https://auth.miniorange.com/moas/rest/customer/add';
            $ch = curl_init ( $url );
            // $current_user = wp_get_current_user();
            $this->email = $email;
            $password = $password;
            $fields = array (
                    'companyName' => $_SERVER ['SERVER_NAME'],
                    'areaOfInterest' => 'TYPO3 SAML Extension',
                    'email' => $this->email,
                    'password' => $password 
            );
            $field_string = json_encode ( $fields );
            
            curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, true );
            curl_setopt ( $ch, CURLOPT_ENCODING, "" );
            curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt ( $ch, CURLOPT_AUTOREFERER, true );
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false ); // required for https urls
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false );
            curl_setopt ( $ch, CURLOPT_MAXREDIRS, 10 );
            curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
                    'Content-Type: application/json',
                    'charset: UTF - 8',
                    'Authorization: Basic' 
            ) );
            curl_setopt ( $ch, CURLOPT_POST, true );
            curl_setopt ( $ch, CURLOPT_POSTFIELDS, $field_string );
            $content = curl_exec ( $ch );
            
            if (curl_errno ( $ch )) {
                
                // if($this->is_connection_issue(curl_errno($ch)))
                //     wp_die("There was an issue connection to Internet. Check if your firewall is allowing outbound connection to port 443.<br><br>In case you are using proxy, go to proxy tab in plugin and configure proxy settings.");
                
                echo 'Request Error:' . curl_error ( $ch );
                exit ();
            }
            
            curl_close ( $ch );
            return $content;
        }
        public function submit_contact($email, $phone, $query)
        {
            $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
            if(!empty($email) or !is_null($email)){
                if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
                    $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                        'Please enter a valid email address','ERROR',
                        \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR, true
                    );
                    $out = GeneralUtility::makeInstance(ListRenderer ::class)
                        ->render([$message]);
                    echo $out;
                    return;
                }
                else{
                    if(empty($query) or is_null($query)){
                        $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                            'Please fill out a valid query','ERROR',
                            \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR, true
                        );
                        $out = GeneralUtility::makeInstance(ListRenderer ::class)
                            ->render([$message]);
                        echo $out;
                        //Flash::error('Please fill out a valid query');
                        return;
                    }
                    else{
                        goto sendMail;
                    }
                }
            }
            else{
                $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                    'Please enter a valid email address','ERROR',
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR, true
                );
                $out = GeneralUtility::makeInstance(ListRenderer ::class)
                    ->render([$message]);
                echo $out;
                return;
            }
            sendMail:
            $url = 'https://auth.miniorange.com/moas/api/notify/send';
            $ch = curl_init($url);

            $customerKey = "16555";
            $apiKey = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";


            $currentTimeInMillis = round(microtime(true) * 1000);
            $stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
            $hashValue = hash("sha512", $stringToHash);
            $customerKeyHeader = "Customer-Key: " . $customerKey;
            $timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
            $authorizationHeader = "Authorization: " . $hashValue;
            $fromEmail = $email;
            $subject = "TYPO3 SAML SP Plugin Support Query";

            $content = '<div >Hello, <br><br><b>Company :</b><a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br><br><b>Phone Number :</b>' . $phone . '<br><br><b>Email :<a href="mailto:' . $fromEmail . '" target="_blank">' . $fromEmail . '</a></b><br><br><b>Query: ' . $query . '</b></div>';

            $test_email_id = 'info@miniorange.com';
            //$support_email_id = 'joomlasupport@miniorange.com';

            $fields = array(
                'customerKey' => $customerKey,
                'sendEmail' => true,
                'email' => array(
                    'customerKey' => $customerKey,
                    'fromEmail' => $fromEmail,
                    'bccEmail' => $test_email_id,
                    'fromName' => 'miniOrange',
                    'toEmail' => $test_email_id,
                    'toName' => $test_email_id,
                    'subject' => $subject,
                    'content' => $content
                ),
            );
            $field_string = json_encode($fields);


            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    # required for https urls

            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $customerKeyHeader,
                $timestampHeader, $authorizationHeader));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);
            $content = curl_exec($ch);

            if (curl_errno($ch)) {
                $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                    'CURL ERROR','Error',
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR, true
                );
                $out = GeneralUtility::makeInstance(ListRenderer ::class)
                    ->render([$message]);
                echo $out;
                return;
            }
            curl_close($ch);
            $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                'Support query sent ! We will get in touch with you shortly.','SUCCESS',
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK, true
            );
            $out = GeneralUtility::makeInstance(ListRenderer ::class)
                ->render([$message]);
            echo $out;
            return $content;

        }


        function check_customer($email,$password,$confirmPassword) {
            $url = "https://auth.miniorange.com/moas/rest/customer/check-if-exists";
            $ch = curl_init ( $url );;
            
            $fields = array (
                    'email' => $email 
            );
            $field_string = json_encode ( $fields );
            
            curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, true );
            curl_setopt ( $ch, CURLOPT_ENCODING, "" );
            curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt ( $ch, CURLOPT_AUTOREFERER, true );
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false ); // required for https urls
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false );
            curl_setopt ( $ch, CURLOPT_MAXREDIRS, 10 );
            curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
                    'Content-Type: application/json',
                    'charset: UTF - 8',
                    'Authorization: Basic' 
            ) );
            curl_setopt ( $ch, CURLOPT_POST, true );
            curl_setopt ( $ch, CURLOPT_POSTFIELDS, $field_string );
            $content = curl_exec ( $ch );
            if (curl_errno ( $ch )) {
                echo 'Error in sending curl Request';
                exit ();
            }
            curl_close ( $ch );
            
            return $content;
        }

        function get_customer_key($email,$password) {
            $url = "https://auth.miniorange.com/moas/rest/customer/key";
            $ch = curl_init ( $url );

            $fields = array (
                    'email' => $email,
                    'password' => $password 
            );
            $field_string = json_encode ( $fields );
            
            curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, true );
            curl_setopt ( $ch, CURLOPT_ENCODING, "" );
            curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt ( $ch, CURLOPT_AUTOREFERER, true );
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false ); // required for https urls
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false );
            curl_setopt ( $ch, CURLOPT_MAXREDIRS, 10 );
            curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
                    'Content-Type: application/json',
                    'charset: UTF - 8',
                    'Authorization: Basic' 
            ) );
            curl_setopt ( $ch, CURLOPT_POST, true );
            curl_setopt ( $ch, CURLOPT_POSTFIELDS, $field_string );
            
            $content = curl_exec ( $ch );
    
            if (curl_errno ( $ch )) {
                echo 'Error in sending curl Request';
                exit ();
            }
            curl_close ( $ch );
            
            return $content;
        }

        function mo_saml_vl($code,$active) {
            $url = "";
            if($active)
                $url = 'https://auth.miniorange.com/moas/api/backupcode/check';
            else
                $url = 'https://auth.miniorange.com/moas/api/backupcode/verify';
            
            $ch = curl_init ( $url );
            
            /* The customer Key provided to you */
            $customerKey = DB::get_option ( 'mo_saml_admin_customer_key' );
            
            /* The customer API Key provided to you */
            $apiKey = DB::get_option ( 'mo_saml_admin_api_key' );
            
            /* Current time in milliseconds since midnight, January 1, 1970 UTC. */
            $currentTimeInMillis = round ( microtime ( true ) * 1000 );
            
            /* Creating the Hash using SHA-512 algorithm */
            $stringToHash = $customerKey . number_format ( $currentTimeInMillis, 0, '', '' ) . $apiKey;
            $hashValue = hash ( "sha512", $stringToHash );
            
            $customerKeyHeader = "Customer-Key: " . $customerKey;
            $timestampHeader = "Timestamp: " . number_format ( $currentTimeInMillis, 0, '', '' );
            $authorizationHeader = "Authorization: " . $hashValue;
            
            $fields = '';
            
            // *check for otp over sms/email
            
            $fields = array (
                    'code' => $code ,
                    'customerKey' => $customerKey,
                    'additionalFields' => array(
                        'field1' => $this->saml_get_current_domain()
                    )
                    
            );

            
            $field_string = json_encode ( $fields );
            
            curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, true );
            curl_setopt ( $ch, CURLOPT_ENCODING, "" );
            curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt ( $ch, CURLOPT_AUTOREFERER, true );
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false ); // required for https urls
            curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false );
            curl_setopt ( $ch, CURLOPT_MAXREDIRS, 10 );
            curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
                    "Content-Type: application/json",
                    $customerKeyHeader,
                    $timestampHeader,
                    $authorizationHeader 
            ) );
            curl_setopt ( $ch, CURLOPT_POST, true );
            curl_setopt ( $ch, CURLOPT_POSTFIELDS, $field_string );
            curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
            curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 );
            
            $content = curl_exec ( $ch );
    
            if (curl_errno ( $ch )) {
                echo 'Error in sending curl Request';
                exit ();
            }
            
            curl_close ( $ch );
            return $content;
        }

        function check_customer_ln(){
		
            $url = 'https://auth.miniorange.com/moas/rest/customer/license';
            $ch = curl_init($url);
            $customerKey = DB::get_option ( 'mo_saml_admin_customer_key' );
            
            $apiKey = DB::get_option ( 'mo_saml_admin_api_key' );
            $currentTimeInMillis = round(microtime(true) * 1000);
            $stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
            $hashValue = hash("sha512", $stringToHash);
            $customerKeyHeader = "Customer-Key: " . $customerKey;
            $timestampHeader = "Timestamp: " . $currentTimeInMillis;
            $authorizationHeader = "Authorization: " . $hashValue;
            $fields = '';
            $fields = array(
                'customerId' => $customerKey,
                'applicationName' => 'php_saml_connector_premium_plan'
            );
            $field_string = json_encode($fields);
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
            curl_setopt( $ch, CURLOPT_ENCODING, "" );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );  
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  # required for https urls
            curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $customerKeyHeader, $timestampHeader, $authorizationHeader));
            curl_setopt( $ch, CURLOPT_POST, true);
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $field_string);
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt( $ch, CURLOPT_TIMEOUT, 20);
            
            $content = curl_exec($ch);
            
            if(curl_errno($ch))
                return false;
            curl_close($ch);
            return $content;		
        }

        function saml_get_current_domain() {
            $http_host = $_SERVER['HTTP_HOST'];
            if(substr($http_host, -1) == '/') {
                $http_host = substr($http_host, 0, -1);
            }
            $request_uri = $_SERVER['REQUEST_URI'];
            if(substr($request_uri, 0, 1) == '/') {
                $request_uri = substr($request_uri, 1);
            }
        
            $is_https = (isset($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') == 0);
            $relay_state = 'http' . ($is_https ? 's' : '') . '://' . $http_host;
            return $relay_state;
        }

    }

    
?>