<?php

namespace Miniorange\Sp\Helper;

use Miniorange\Sp\Helper\Constants;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Messaging\Renderer\ListRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Miniorange\Sp\Helper\Utilities;

class CustomerSaml
{

    public $email;

    //This function is used to register the users in miniOrange
    function create_customer($email, $password)
    {

        $url = Constants::HOSTNAME . '/moas/rest/customer/add';
        $this->email = $email;
        $password = $password;
        $fields = array(
            'companyName' => $_SERVER['SERVER_NAME'],
            'areaOfInterest' => Constants::AREA_OF_INTEREST,
            'email' => $this->email,
            'password' => $password
        );
        $field_string = json_encode($fields);

        $ch = $this->prepareCurlOptions($url, $field_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'charset: UTF - 8',
            'Authorization: Basic'
        ));

        $response = curl_exec($ch);
        error_log("create_customer response : " . print_r($response, true));

        if (curl_errno($ch)) {
            echo 'Request Error:' . curl_error($ch);
            exit ();
        }

        curl_close($ch);
        return $response;
    }

    //This function is use to send query to support team
    public function submit_contact($email, $phone, $query)
    {
        error_log(" TYPO3 SUPPORT QUERY : ");
        sendMail:
        $url = Constants::HOSTNAME . '/moas/api/notify/send';
        $ch = curl_init($url);

        $subject = "TYPO3 SAML SP Free Plugin Query ";

        $customerKey = Utilities::fetch_cust(Constants::CUSTOMER_KEY);
        $apiKey = Utilities::fetch_cust(Constants::CUSTOMER_API_KEY);;

        if ($customerKey == "") {
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

        $support_email_id = 'magentosupport@xecurify.com';

        $fields = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey' => $customerKey,
                'fromEmail' => $email,
                'fromName' => 'miniOrange',
                'toEmail' => $support_email_id,
                'toName' => $support_email_id,
                'bccEmail' => "info@xecurify.com",
                'subject' => $subject,
                'content' => $content
            ),
        );


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

        if (curl_errno($ch)) {
            $message = GeneralUtility::makeInstance(FlashMessage::class, 'CURL ERROR', 'Error', FlashMessage::ERROR, true);
            $messageArray = array($message);
            $out = GeneralUtility::makeInstance(ListRenderer ::class)->render($messageArray);
            echo $out;
            return;
        }

        curl_close($ch);

        return $content;
    }

    // This function checks for the existing users
    function check_customer($email, $password)
    {
        $url = Constants::HOSTNAME . '/moas/rest/customer/check-if-exists';
        $ch = curl_init($url);;

        $fields = array(
            'email' => $email
        );
        $field_string = json_encode($fields);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // required for https urls
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'charset: UTF - 8',
            'Authorization: Basic'
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);

        $content = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Error in sending curl Request';
            exit ();
        }
        curl_close($ch);

        return $content;
    }

    // This function is used to fetch customer key
    function get_customer_key($email, $password)
    {
        $url = Constants::HOSTNAME . '/moas/rest/customer/key';
        $ch = curl_init($url);

        $fields = array(
            'email' => $email,
            'password' => $password
        );
        $field_string = json_encode($fields);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // required for https urls
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'charset: UTF - 8',
            'Authorization: Basic'
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);

        $content = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Error in sending curl Request';
            exit ();
        }
        curl_close($ch);

        return $content;
    }

    function createAuthHeader($customerKey, $apiKey)
    {
        $currentTimestampInMillis = round(microtime(true) * 1000);
        $currentTimestampInMillis = number_format($currentTimestampInMillis, 0, '', '');

        $stringToHash = $customerKey . $currentTimestampInMillis . $apiKey;
        $authHeader = hash("sha512", $stringToHash);

        $header = [
            "Content-Type: application/json",
            "Customer-Key: $customerKey",
            "Timestamp: $currentTimestampInMillis",
            "Authorization: $authHeader"
        ];
        return $header;
    }

    function callAPI($url, $jsonData = [], $headers = ["Content-Type: application/json"])
    {
        $options = [
            CURLOPT_RETURNTRANSFER => true,  // Return the response instead of printing it
            CURLOPT_FOLLOWLOCATION => true,  // Follow redirects
            CURLOPT_MAXREDIRS => 10,        // Maximum number of redirects to follow
            CURLOPT_SSL_VERIFYPEER => false, // Disable SSL certificate verification (for testing purposes)
            CURLOPT_ENCODING => "",
            CURLOPT_AUTOREFERER => true,
            CURLOPT_TIMEOUT => 0,
            // Add more options as needed
        ];


        $data = in_array("Content-Type: application/x-www-form-urlencoded", $headers)
            ? (!empty($jsonData) ? http_build_query($jsonData) : "") : (!empty($jsonData) ? json_encode($jsonData) : "");

        $method = !empty($data) ? 'POST' : 'GET';

        $ch = curl_init();
        curl_setopt_array($ch, $options);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $response = curl_exec($ch);
        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        return $response;
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

    //This function is used to notify the support team that the plugin has been installed successfully
    function submit_to_magento_team(
        $q_email,
        $sub,
        $values,
        $typo3Version
    ) {
        $url =  Constants::HOSTNAME . "/moas/api/notify/send";
        $customerKey =  Constants::DEFAULT_CUSTOMER_KEY;
        $apiKey =  Constants::DEFAULT_API_KEY;

        $fields1 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "nitesh.pamnani@xecurify.com",
                'bccEmail'      => "rutuja.sonawane@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "nitesh.pamnani@xecurify.com",
                'toName'        => "Nitesh",
                'subject'       => "Typo3 SAML SP free Plugin $sub : $q_email",
                'content'       => " Admin UserName = $q_email, Site= $values[0], Typo3 Version = $typo3Version"
            ),
        );

        $fields2 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "rushikesh.nikam@xecurify.com",
                'bccEmail'      => "raj@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "rushikesh.nikam@xecurify.com",
                'toName'        => "Rushikesh",
                'subject'       => "Typo3 SAML SP free Plugin $sub : $q_email",
                'content'       => " Admin Email = $q_email, Site= $values[0], Typo3 Version = $typo3Version"
            ),
        );
        $field_string1 = json_encode($fields1);
        $field_string2 = json_encode($fields2);
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response1 = self::callAPI($url, $fields1, $authHeader);
        $response2 = self::callAPI($url, $fields2, $authHeader);
        return true;
    }

    //This function is used to track the test configuration results
    function submit_to_magento_team_core_config_data(
        $sub,
        $content,
        $values,
        $site
    ) {
        $url =  Constants::HOSTNAME . "/moas/api/notify/send";
        $customerKey =  Constants::DEFAULT_CUSTOMER_KEY;
        $apiKey =  Constants::DEFAULT_API_KEY;
        $content = json_encode($content);

        $fields1 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "nitesh.pamnani@xecurify.com",
                'bccEmail'      => "rutuja.sonawane@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "nitesh.pamnani@xecurify.com",
                'toName'        => "Nitesh",
                'subject'       => "Typo3 SAML SP free Plugin $sub site: $site",
                'content'       => "Attributes Received: $content <br><br>,IDP Configurations: $values"
            ),
        );

        $fields2 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "rushikesh.nikam@xecurify.com",
                'bccEmail'      => "raj@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "rushikesh.nikam@xecurify.com",
                'toName'        => "Rushikesh",
                'subject'       => "Typo3 SAML SP free Plugin $sub site: $site",
                'content'       => " $content <br>, $values"
            ),
        );
        $field_string1 = json_encode($fields1);
        $field_string2 = json_encode($fields2);
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response1 = self::callAPI($url, $fields1, $authHeader);
        $response2 = self::callAPI($url, $fields2, $authHeader);
        return true;
    }

    //This function is used to notify that the auto create user imit has been exceeded
    function submit_to_magento_team_autocreate_limit_exceeded($site, $typo3Version) {
        $url =  Constants::HOSTNAME . "/moas/api/notify/send";
        $customerKey =  Constants::DEFAULT_CUSTOMER_KEY;
        $apiKey =  Constants::DEFAULT_API_KEY;

        $fields1 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "nitesh.pamnani@xecurify.com",
                'bccEmail'      => "rutuja.sonawane@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "nitesh.pamnani@xecurify.com",
                'toName'        => "Nitesh",
                'subject'       => "Typo3 SAML SP free Plugin AUTOCREATE USER LIMIT EXEEDED site: $site",
                'content'       => "Site: $site, Typo3 Version = $typo3Version"
            ),
        );

        $fields2 = array(
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => array(
                'customerKey'   => $customerKey,
                'fromEmail'     => "rushikesh.nikam@xecurify.com",
                'bccEmail'      => "raj@xecurify.com",
                'fromName'      => 'miniOrange',
                'toEmail'       => "rushikesh.nikam@xecurify.com",
                'toName'        => "Rushikesh",
                'subject'       => "Typo3 SAML SP free Plugin AUTOCREATE USER LIMIT EXEEDED site: $site",
                'content'       => "Site: $site, Typo3 Version = $typo3Version"
            ),
        );
        $field_string1 = json_encode($fields1);
        $field_string2 = json_encode($fields2);
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response1 = self::callAPI($url, $fields1, $authHeader);
        $response2 = self::callAPI($url, $fields2, $authHeader);
        return true;
    }

}
