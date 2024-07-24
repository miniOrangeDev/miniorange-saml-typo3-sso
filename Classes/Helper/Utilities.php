<?php

namespace Miniorange\Sp\Helper;

use Exception;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Messaging\Renderer\ListRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Information\Typo3Version;

const SEP = DIRECTORY_SEPARATOR;

class Utilities
{
    /**
     * Get the Private Key File Path
     * @return string
     */
    public static function getPrivateKey()
    {
        return self::getResourceDir() . DIRECTORY_SEPARATOR . Constants::SP_KEY;
    }

    /**
     * Get resource director path
     * rn string
     */
    public static function getResourceDir()
    {
        global $sep;
        $relPath = self::getExtensionRelativePath();
        $sep = substr($relPath, -1);
        $resFolder = $relPath . 'Helper' . $sep . 'resources' . $sep;

        return $resFolder;
    }

    public static function getExtensionRelativePath()
    {
        $extRelativePath = PathUtility::getAbsoluteWebPath(self::getExtensionAbsolutePath());
        return $extRelativePath;
    }

    public static function getExtensionAbsolutePath()
    {
        $extAbsPath = ExtensionManagementUtility::extPath('sp');
        return $extAbsPath;
    }

    //Function to fetch Typo3 instance version
    public static function getTypo3Version()
    {
        $version = new Typo3Version();
        return $version->getVersion();
    }

    public static function fetchUserFromUsername($username)
    {
        $typo3Version = self::getTypo3Version();
        $table = Constants::TABLE_FE_USERS;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        // Remove all restrictions but add DeletedRestriction again
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        if($typo3Version > 12)
        {
            $user = $queryBuilder->select('*')->from($table)->where(
                $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username))
            )->executeQuery()->fetch();
        }
        else
        {
            $user = $queryBuilder->select('*')->from($table)->where(
                $queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter($username))
            )->execute()->fetch();
        }

        if (null == $user) {
            self::log_php_error("user not found: ");
            return false;
        }

        self::log_php_error("User found");
        return $user;
    }


//---------FETCH CUSTOMER DETAILS-------------------------

    public static function log_php_error($msg = "", $obj = null)
    {
        error_log($msg . ": " . print_r($obj, true) . "\n\n");
    }

//---------------------FETCH UID_FROM_USERNAME--------------------------

    public static function fetchFromTable($col, $table)
    {
        $typo3Version = self::getTypo3Version();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        if($typo3Version > 12)
        {
            $variable = $queryBuilder->select($col)->from($table)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->executeQuery()->fetch();
        }
        else
        {
            $variable = $queryBuilder->select($col)->from($table)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetch();
        }
        return is_array($variable) ? $variable[$col] : $variable;
    }

//----------Fetch From Any Table---------------------------------------

    public static function updateTable($col, $val, $table)
    {
        $typo3Version = self::getTypo3Version();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        if($typo3Version > 12)
        {
            $queryBuilder->update($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set($col, $val)
            ->executeStatement();
        }
        else
        {
            $queryBuilder->update($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set($col, $val)
            ->execute();
        }
    }

// -------------UPDATE TABLE---------------------------------------

    public static function fetchUidFromGroupName($name, $table = "fe_groups")
    {
        $typo3Version = self::getTypo3Version();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        if($typo3Version > 12)
        {
            $rows = $queryBuilder->select('uid')
            ->from($table)
            ->where($queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($name, \PDO::PARAM_STR)))
            ->executeQuery()
            ->fetch();
        }
        else{
            $rows = $queryBuilder->select('uid')
            ->from($table)
            ->where($queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($name, \PDO::PARAM_STR)))
            ->execute()
            ->fetch();
        }
        return is_array($rows) ? $rows['uid'] : $rows;
    }

//------------Fetch UID from Groups

    public static function update_cust($column, $value)
    {
        $typo3Version = self::getTypo3Version();
        if (self::fetch_cust('id') == null) {
            self::insertValue();
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('customer');
        if($typo3Version > 12)
        {
            $queryBuilder->update('customer')->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set($column, $value)->executeStatement();
        }
        else
        {
            $queryBuilder->update('customer')->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set($column, $value)->execute();
        }
    }

//---------- FETCH CUSTOMER DETAILS --------------------------------

    public static function fetch_cust($col)
    {
        $typo3Version = self::getTypo3Version();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('customer');
        if($typo3Version > 12)
        {
            $variable = $queryBuilder->select($col)->from('customer')->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->executeQuery()->fetch();
        }
        else
        {
            $variable = $queryBuilder->select($col)->from('customer')->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->execute()->fetch();
        }
        return is_array($variable) ? $variable[$col] : $variable;
    }

//---------INSERT CUSTOMER DETAILS--------------

    public static function insertValue()
    {
        $typo3Version = self::getTypo3Version();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('customer');
        if($typo3Version > 12)
        {
            $affectedRows = $queryBuilder->insert('customer')->values(['id' => '1'])->executeStatement();
        }
        else
        {
            $affectedRows = $queryBuilder->insert('customer')->values(['id' => '1'])->execute();
        }
    }

    public static function getAlternatePrivateKey()
    {
        return self::getResourceDir() . DIRECTORY_SEPARATOR . Constants::SP_ALTERNATE_KEY;
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
        error_log("resDir : " . $imageDir);
        $iconDir = self::getExtensionRelativePath() . SEP . 'Resources' . SEP . 'Public' . SEP . 'Icons' . SEP;
        error_log("iconDir : " . $iconDir);
        return $iconDir . $imgFileName;
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

    public static function showErrorFlashMessage($message, $header = "ERROR")
    {
        $typo3Version = self::getTypo3Version();
        if($typo3Version > 12)
        {
            $message = GeneralUtility::makeInstance(FlashMessage::class, $message, $header, ContextualFeedbackSeverity::ERROR);
        }
        else
        {
            $message = GeneralUtility::makeInstance(FlashMessage::class, $message, $header, FlashMessage::ERROR);
        }
        $messageArray = array($message);
        $out = GeneralUtility::makeInstance(ListRenderer ::class)->render($messageArray);
        echo $out;
    }

    public static function showSuccessFlashMessage($message, $header = "OK")
    {
        $typo3Version = self::getTypo3Version();
        if($typo3Version > 12)
        {
            $message = GeneralUtility::makeInstance(FlashMessage::class, $message, $header, ContextualFeedbackSeverity::OK);
        }
        else
        {
            $message = GeneralUtility::makeInstance(FlashMessage::class, $message, $header, FlashMessage::OK);
        }
        error_log(print_r($message, true) . "\n\n");
        $messageArray = array($message);
        $out = GeneralUtility::makeInstance(ListRenderer ::class)->render($messageArray);
        echo $out;
    }

    // Fuction to update the SAML Table Entries
    public static function updateTableSaml($col, $val)
    {
        $typo3Version = self::getTypo3Version();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Constants::TABLE_SAML);
        if($typo3Version > 12)
        {
            $queryBuilder->update(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set($col, $val)->executeStatement();
        }
        else
        {
            $queryBuilder->update(Constants::TABLE_SAML)->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, PDO::PARAM_INT)))->set($col, $val)->execute();
        }
    }
}
