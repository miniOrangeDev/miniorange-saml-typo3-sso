<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Miniorange\Sp\Domain\Repository\UserGroup;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\Connection;

abstract class AbstractUserGroupRepository
{

    protected $tableName;

    public function __construct()
    {
        $this->setTableName();
    }

    abstract protected function setTableName(): void;

    public function findAll($typo3Version): array
    {
        $col = '*'; 
        $queryBuilder = $this->getQueryBuilder();
	$result = $queryBuilder->select($col)->from($this->tableName)->where($queryBuilder->expr()->eq('uid',$queryBuilder->createNamedParameter(1, Connection::PARAM_INT)))->executeQuery()->fetchAllAssociative();
	return $result;
        
    }


    protected function getQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
    }
}
