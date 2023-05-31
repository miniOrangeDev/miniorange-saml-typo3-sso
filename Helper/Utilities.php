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
            return $extAbsPath;
        }

        public static function getExtensionRelativePath()
        {
            $extRelativePath = PathUtility::getAbsoluteWebPath(self::getExtensionAbsolutePath());
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
            $certificate = str_replace("-----BEGIN CERTIFICATE-----", "", $certificate);
            $certificate = str_replace("-----END CERTIFICATE-----", "", $certificate);
            $certificate = str_replace(" ", "", $certificate);
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

    }
