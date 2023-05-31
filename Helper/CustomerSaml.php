<?php

namespace Miniorange\Helper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CustomerSaml {

    public $email;
    public $phone;

    private $defaultCustomerKey = Constants::DEFAULT_CUSTOMER_KEY;
    private $defaultApiKey = Constants::HOSTNAME;

    function create_customer($email,$password) {

        $url = Constants::HOSTNAME.'/moas/rest/customer/add';
        // $current_user = wp_get_current_user();
        $this->email = $email;
        $password = $password;
        $fields = array (
            'companyName' => $_SERVER['SERVER_NAME'],
            'areaOfInterest' => Constants::AREA_OF_INTEREST,
            'email' => $this->email,
            'password' => $password
        );
        $field_string = json_encode ( $fields );

        $ch = $this->prepareCurlOptions($url,$field_string);
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
            'Content-Type: application/json',
            'charset: UTF - 8',
            'Authorization: Basic'
        ) );

        $response = curl_exec( $ch );
        error_log("create_customer response : ".print_r($response,true));

        if (curl_errno ( $ch )) {
            echo 'Request Error:' . curl_error ( $ch );
            exit ();
        }

        curl_close ( $ch );
        return $response;
    }

    public function submit_contact($email, $phone, $query)
    {
        $this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        error_log(" TYPO3 SUPPORT QUERY : ");

        sendMail:
        $url = Constants::HOSTNAME.'/moas/api/notify/send';
        $subject = "miniOrange Typo3 SAML Free version 2.0.2 Support";

        $customerKey = Utilities::fetch_cust(Constants::CUSTOMER_KEY);
        $apiKey      = Utilities::fetch_cust(Constants::CUSTOMER_API_KEY);;

        if($customerKey==""){
            $customerKey= $this->defaultCustomerKey ;
            $apiKey = "$this->defaultApiKey";
        }

        $currentTimeInMillis = round(microtime(true) * 1000);
        $stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
        $hashValue = hash("sha512", $stringToHash);
        $customerKeyHeader = "Customer-Key: " . $customerKey;
        $timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
        $authorizationHeader = "Authorization: " . $hashValue;

        $content = '<div >Hello, <br><br><b>Company :</b><a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br><br><b>Phone Number :</b>' . $phone . '<br><br><b>Email :<a href="mailto:' . $email . '" target="_blank">' . $email . '</a></b><br><br><b>Query: ' . $query . '</b></div>';

        $support_email_id = 'magentosupport@xecurify.com';

        $fields = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey' => $customerKey,
                'fromEmail'   => $email,
                'fromName'    => 'miniOrange',
                'toEmail'     => $support_email_id,
                'toName'      => $support_email_id,
                'subject'     => $subject,
                'content'     => $content
            ),
        );
        $field_string = json_encode($fields);

        error_log("TYPO3 support content : ".print_r($content,true));

        $ch = $this->prepareCurlOptions($url,$field_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
                            array("Content-Type: application/json",
                                $customerKeyHeader,
                                $timestampHeader,
                                $authorizationHeader)
        );

        $response = curl_exec($ch);
        error_log("submit_contact response : ".print_r($response,true));

        if (curl_errno($ch)) {
            $message = GeneralUtility::makeInstance(FlashMessage::class,'CURL ERROR','Error',FlashMessage::ERROR,true);
            $out = GeneralUtility::makeInstance(ListRenderer ::class)->render([$message]);
            echo $out;
            return;
        }

        curl_close($ch);
        return $response;

    }

    function check_customer($email,$password) {
        $url = Constants::HOSTNAME."/moas/rest/customer/check-if-exists";
        $fields = array (
            'email' => $email
        );
        $field_string = json_encode ( $fields );

        $ch = $this->prepareCurlOptions($url,$field_string);
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
            'Content-Type: application/json',
            'charset: UTF - 8',
            'Authorization: Basic'
        ) );

        $response = curl_exec ( $ch );
        error_log("check_customer response : ".print_r($response,true));

        if (curl_errno ( $ch )) {
            echo 'Error in sending curl Request';
            exit ();
        }
        curl_close ( $ch );

        return $response;
    }

    function get_customer_key($email,$password) {
        $url = Constants::HOSTNAME."/moas/rest/customer/key";
        $fields = array (
            'email' => $email,
            'password' => $password
        );
        $field_string = json_encode ( $fields );

        $ch = $this->prepareCurlOptions($url,$field_string);
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array (
            'Content-Type: application/json',
            'charset: UTF - 8',
            'Authorization: Basic'
        ) );

        $response = curl_exec ( $ch );
        error_log("get_customer_key response : ".print_r($response,true));

        if (curl_errno ( $ch )) {
            echo 'Error in sending curl Request';
            exit ();
        }
        curl_close ( $ch );

        return $response;
    }

    function mo_cust_vl($customerKey,$apiKey,$code,$active) {
        $url = "";
        if($active)
            $url = Constants::HOSTNAME.'/moas/api/backupcode/check';
        else
            $url = Constants::HOSTNAME.'/moas/api/backupcode/verify';

        $ch = curl_init ( $url );

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
                'field1' => $this->mo_get_current_domain()
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
        error_log("mo_cust_vl response : ".print_r($content,true));

        if (curl_errno ( $ch )) {
            echo 'Error in sending curl Request';
            exit ();
        }

        curl_close ( $ch );
        return $content;
    }

    function check_customer_ln($customerKey,$apiKey){

        $url = Constants::HOSTNAME.'/moas/rest/customer/license';
        $ch = curl_init($url);

        $currentTimeInMillis = round(microtime(true) * 1000);
        $stringToHash = $customerKey . number_format($currentTimeInMillis, 0, '', '') . $apiKey;
        $hashValue = hash("sha512", $stringToHash);
        $customerKeyHeader = "Customer-Key: " . $customerKey;
        $timestampHeader = "Timestamp: " . $currentTimeInMillis;
        $authorizationHeader = "Authorization: " . $hashValue;
        $fields = '';
        $fields = array(
            'customerId' => $customerKey,
            'applicationName' => Constants::APPLICATION_NAME
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
        error_log("check_cust_ln response : ".print_r($content,true));

        if(curl_errno($ch))
            return false;
        curl_close($ch);
        return $content;
    }

    function updateStatus($key,$apikey,$code) {
        $url = Constants::HOSTNAME.'/moas/api/backupcode/updatestatus';
        $ch = curl_init($url);

        $currentTimeInMillis = round(microtime(true) * 1000);
        $stringToHash = $key . number_format($currentTimeInMillis, 0, '', '') . $apikey;
        $hashValue = hash("sha512", $stringToHash);
        $customerKeyHeader = "Customer-Key: " . $key;
        $timestampHeader = "Timestamp: " . number_format($currentTimeInMillis, 0, '', '');
        $authorizationHeader = "Authorization: " . $hashValue;

        $fields = array (
            'code' => $code ,
            'customerKey' => $key,
            'additionalFields' => array(
                'field1' => $this->mo_get_current_domain()
            )
        );
        $field_string = json_encode ( $fields );

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // required for https urls

        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                $customerKeyHeader,
                $timestampHeader,
                $authorizationHeader
            )
        );
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $content = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Request Error:' . curl_error($ch);
        }
        curl_close($ch);
        error_log("updateStatus TResponse".print_r($content,true));

        return $content;
    }

    function mo_get_current_domain() {
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

    function check_internet_connection() {
        return (bool) @fsockopen('login.xecurify.com', 443, $iErrno, $sErrStr, 5);
    }

    function prepareCurlOptions($url, $field_string){

        $ch = curl_init($url);
        curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt ( $ch, CURLOPT_ENCODING, "" );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false ); // required for https urls
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt ( $ch, CURLOPT_MAXREDIRS, 10 );
        curl_setopt ( $ch, CURLOPT_POST, true );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $field_string );

        return $ch;
    }

}