<?php


namespace Miniorange\MiniorangeSaml\Controller;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class LogoutController extends  ActionController
{

    public function checkAction(){

        $this->cacheService->clearPageCache([$GLOBALS['TSFE']->id]);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('saml');
        $logout_url = $queryBuilder->select('slo_url')->from('saml')->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)))->execute()->fetchColumn(0);

            if (isset($_REQUEST['SAMLResponse'])) {
                $samlResponse = $_REQUEST['SAMLResponse'];
                $samlResponse = base64_decode($samlResponse);
                if (array_key_exists('SAMLResponse', $_GET) && !empty($_GET['SAMLResponse'])) {
                    $samlResponse = gzinflate($samlResponse);
                }
                $document = new \DOMDocument();
                $document->loadXML($samlResponse);
                $samlResponseXml = $document->firstChild;
                $doc = $document->documentElement;
//              $xpath = new \DOMXpath($document);
//              $xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
//              $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');

                if ($samlResponseXml->localName == 'LogoutResponse') {
                    header('Location: ' . $logout_url);
                    die;
                }
            }
        }
}