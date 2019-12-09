<?php
namespace Miniorange\MiniorangeSaml\Domain\Model;

/***
 *
 * This file is part of the "etitle" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Miniorange <info@xecurify.com>
 *
 ***/

/**
 * Besaml
 */
class Besaml extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    public $idp_name = '';

    /**
     * @param $idp_name
     */
    public function __construct($idp_name)
    {
        $this->setIdpName($idp_name);
    }

    /**
     * @param $idp_name
     */
    public function setIdpName($idp_name)
    {
        $this->idp_name = $idp_name;
    }
}
