<?php
namespace Miniorange\classes;

//if(!class_exists("DB")){
//    require_once dirname(__FILE__) . '/helper/DB.php';
//}
use Miniorange\helper\Constants;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Messaging\Renderer\ListRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
USE Miniorange\helper\Utilities;

class CustomerSaml{

        public $email;

        function create_customer($email,$password) {

            $url = 'https://login.xecurify.com/moas/rest/customer/add';
            $ch = curl_init($url);
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
            error_log("create_customer response : ".$content);
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
            $this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

            error_log(" TYPO3 SUPPORT QUERY : ");

            sendMail:
                 $url = 'https://login.xecurify.com/moas/api/notify/send';
              $ch = curl_init($url);

            $subject = "TYPO3 miniOrange SAML SP Plugin Support Query";

            $customerKey = Utilities::fetch_cust(Constants::CUSTOMER_KEY);
            $apiKey      = Utilities::fetch_cust(Constants::CUSTOMER_API_KEY);;

            if($customerKey==""){
                    $customerKey = "16555";
                    $apiKey = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
            }

            $currentTimeInMillis = round(microtime(true) * 1000);
            $stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
            $hashValue = hash("sha512", $stringToHash);
            $customerKeyHeader = "Customer-Key: " . $customerKey;
            $timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
            $authorizationHeader = "Authorization: " . $hashValue;

			$content = '<div >Hello, <br><br><b>Company :</b><a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br><br><b>Phone Number :</b>' . $phone . '<br><br><b>Email :<a href="mailto:' . $email . '" target="_blank">' . $email . '</a></b><br><br><b>Query: ' . $query . '</b></div>';

            $support_email_id = 'info@xecurify.com';

            $fields = array(
                'customerKey' => $customerKey,
                'sendEmail' => true,
                'email' => array(
                    'customerKey' => $customerKey,
                    'fromEmail'   => $email,
										'fromName'    => 'miniOrange',
										'toEmail'     => $support_email_id,
										'toName'      => $support_email_id,
										'bccEmail'    => "saml2support@xecurify.com",
                    'subject'     => $subject,
                    'content'     => $content
                ),
            );

			error_log("TYPO3 support content : ".print_r($content,true));

            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $customerKeyHeader,
                    $timestampHeader, $authorizationHeader));
            $field_string = json_encode($fields);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		  			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);# required for https urls
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);

          $content = curl_exec($ch);
          error_log("submit_contact rsponse : ".$content);
          if (curl_errno($ch)) {
                $message = GeneralUtility::makeInstance(FlashMessage::class,'CURL ERROR','Error',FlashMessage::ERROR,true);
                $out = GeneralUtility::makeInstance(ListRenderer ::class)->render([$message]);
                echo $out;
                return;
           }

          curl_close($ch);
          return $content;

        }

        function check_customer($email,$password) {
            $url = "https://login.xecurify.com/moas/rest/customer/check-if-exists";
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
            error_log("check_customer response : ".$content);
            if (curl_errno ( $ch )) {
                echo 'Error in sending curl Request';
                exit ();
            }
            curl_close ( $ch );

            return $content;
        }

        function get_customer_key($email,$password) {
            $url = "https://login.xecurify.com/moas/rest/customer/key";
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

            error_log("get_customer_key response : ".$content);

            if (curl_errno ( $ch )) {
                echo 'Error in sending curl Request';
                exit ();
            }
            curl_close ( $ch );

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