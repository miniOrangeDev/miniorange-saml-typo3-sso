<?php

    namespace Miniorange\Helper;

    use Exception;
    use PDO;
    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
    use TYPO3\CMS\Core\Messaging\FlashMessage;
    use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
    use TYPO3\CMS\Core\Messaging\FlashMessageService;
    use TYPO3\CMS\Core\Messaging\Renderer\ListRenderer;
    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use TYPO3\CMS\Core\Utility\PathUtility;

    const SEP = DIRECTORY_SEPARATOR;


	class Utilities
    {

        /**
         * Get resource director path
         * rn string
         */
        public static function submit_contact($email, $phone, $query)
        {
            $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

            error_log(" TYPO3 SUPPORT QUERY : ");

            sendMail:
                 $url = 'https://login.xecurify.com/moas/api/notify/send';
                 $ch = curl_init($url);

            $subject = "TYPO3 miniOrange SAML SP Plugin Support Query";

            $customerKey = Utilities::fetch_cust(Constants::CUSTOMER_KEY);
            $apiKey      = Utilities::fetch_cust(Constants::CUSTOMER_API_KEY);
            
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


        public static function getResourceDir()
        {
            global $sep;
            $relPath = self::getExtensionRelativePath();
            $sep = substr($relPath, -1);
            return $relPath . 'Helper' . $sep . 'resources' . $sep;
        }

        public static function getExtensionAbsolutePath()
        {
            $extAbsPath = ExtensionManagementUtility::extPath('miniorange_saml');
//            error_log("extensionAbsolutePath : " . $extAbsPath);
            return $extAbsPath;
        }

        public static function getExtensionRelativePath()
        {
            $extRelativePath = PathUtility::getAbsoluteWebPath(self::getExtensionAbsolutePath());
//            error_log("extRelativePath : " . $extRelativePath);
            return $extRelativePath;
        }

        /**
         * This function checks if a value is set or
         * empty. Returns true if value is empty
         *
         * @param $value - references the variable passed.
         * @return True or False
         */
        public static function isBlank($value)
        {
            if (!isset($value) || empty($value)) return TRUE;
            return FALSE;
        }

        /**
         * Get the Private Key File Path
         * @return string
         */
        public static function getPrivateKey()
        {
            return self::getResourceDir() . DIRECTORY_SEPARATOR . Constants::SP_KEY;
        }

        /**
         *---------FETCH CUSTOMER DETAILS-------------------------
         */
        public static function fetch_cust($col)
        {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('customer');
            $variable = $queryBuilder->select($col)->from('customer')->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->execute()->fetchColumn(0);
            return $variable;
        }

        /**
         * --------- UPDATE CUSTOMER DETAILS --------------------------------
         */
        public static function update_cust($column, $value)
        {
            if (self::fetch_cust('id') == null) {
                self::insertValue();
            }
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('customer');
            $queryBuilder->update('customer')->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set($column, $value)->execute();
        }

//----------Fetch From Any Table---------------------------------------
        public static function fetchFromTable($col,$table)
        {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            return $queryBuilder->select($col)->from($table)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);
        }

// -------------UPDATE TABLE---------------------------------------
        public static function updateTable($col, $val, $table)
        {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->update($table)
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set($col, $val)
                ->execute();
        }

//------------Fetch UID from Groups
        public static function fetchUidFromGroupName($name, $table="fe_groups")
        {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $rows =  $queryBuilder->select('uid')
                ->from($table)
                ->where($queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($name, \PDO::PARAM_STR)))
                ->execute()
                ->fetchColumn();
            return $rows;
        }

        /**
         *---------INSERT CUSTOMER DETAILS--------------
         */
        public static function insertValue()
        {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('customer');
            $affectedRows = $queryBuilder->insert('customer')->values(['id' => '1'])->execute();
        }

        /**
         * Get the Public Key File Path
         * @return string
         */
        public static function getPublicKey()
        {
            return self::getResourceDir() . DIRECTORY_SEPARATOR . Constants::SP_KEY;
        }

        /**
         * Get Image Resource URL
         */
        public static function getImageUrl($imgFileName)
        {
            $imageDir = self::getResourceDir() . SEP . 'images' . SEP;
//            error_log("resDir : " . $imageDir);
            $iconDir = self::getExtensionRelativePath() . SEP . 'Resources' . SEP . 'Public' . SEP . 'Icons' . SEP;
//            error_log("iconDir : " . $iconDir);
            return $iconDir . $imgFileName;
        }

        /**
         * Get the base url of the site.
         * @return string
         */
        public static function getBaseUrl()
        {
            $pageURL = 'http';

            if ((isset($_SERVER["HTTPS"])) && ($_SERVER["HTTPS"] == "on"))
                $pageURL .= "s";

            $pageURL .= "://";

            if ($_SERVER["SERVER_PORT"] != "80")
                $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"];
            else
                $pageURL .= $_SERVER["SERVER_NAME"];

            return $pageURL;
        }

        /**
         * The function returns the current page URL.
         * @return string
         */
        public static function currentPageUrl()
        {
            return self::getBaseUrl() . $_SERVER["REQUEST_URI"];
        }

        public static function check_certificate_format($certificate)
        {
            if (!@openssl_x509_read($certificate)) {
                throw new Exception("Certificate configured in the connector is in wrong format");
            } else {
                return 1;
            }
        }

        /**
         * This function sanitizes the certificate
         */
        public static function sanitize_certificate($certificate)
        {
            $certificate = trim($certificate);
            $certificate = preg_replace("/[\r\n]+/", "", $certificate);
            $certificate = str_replace("-", "", $certificate);
            $certificate = str_replace("BEGIN CERTIFICATE", "", $certificate);
            $certificate = str_replace("END CERTIFICATE", "", $certificate);
            $certificate = str_replace(" ", "", $certificate);
            $certificate = chunk_split($certificate, 64, "\r\n");
            $certificate = "-----BEGIN CERTIFICATE-----\r\n" . $certificate . "-----END CERTIFICATE-----";
            return $certificate;
        }

        public static function desanitize_certificate($certificate)
        {
            $certificate = preg_replace("/[\r\n]+/", "", $certificate);
            //$certificate = str_replace( "-", "", $certificate );
            $certificate = str_replace("-----BEGIN CERTIFICATE-----", "", $certificate);
            $certificate = str_replace("-----END CERTIFICATE-----", "", $certificate);
            $certificate = str_replace(" ", "", $certificate);
            //$certificate = chunk_split($certificate, 64, "\r\n");
            //$certificate = "-----BEGIN CERTIFICATE-----\r\n" . $certificate . "-----END CERTIFICATE-----";
            return $certificate;
        }

        //---------------------FETCHUID_FROM_USERNAME_check_for_disabled user--------------------------
        public static function fetchUserFromUsername($username)
        {
            $table = Constants::TABLE_FE_USERS;
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            // Remove all restrictions but add DeletedRestriction again
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $var_uid = $queryBuilder->select('*')->from($table)->where(
                $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username))
            )->execute()->fetch();
            if(null == $var_uid){
//                error_log("uid null: ".print_r($var_uid,true));
                return false;
            }
            return $var_uid;
        }


        public static function showErrorFlashMessage($message, $header="ERROR"){
            $message = GeneralUtility::makeInstance(FlashMessage::class,$message,$header,FlashMessage::ERROR);
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $out = GeneralUtility::makeInstance(ListRenderer ::class)->render([$message]);
            echo $out;
        }

        public static function showSuccessFlashMessage($message, $header="OK"){
            $message = GeneralUtility::makeInstance(FlashMessage::class, $message, $header, FlashMessage::OK);
            $out = GeneralUtility::makeInstance(ListRenderer ::class)->render([$message]);
            echo $out;
        }

        public static function log_php_error($msg="",$obj){
            error_log($msg.": ".print_r($obj,true));
        }

    }
