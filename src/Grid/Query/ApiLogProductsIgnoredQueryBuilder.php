<?php
/**
 * 2007-2018 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace EO\ApiLog\Grid\Query;

use Exception;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\DoctrineSearchCriteriaApplicatorInterface;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

/**
 * Class ApiLogProductsIgnoredQueryBuilder.
 */
final class ApiLogProductsIgnoredQueryBuilder extends AbstractDoctrineQueryBuilder
{
    /**
     * @var DoctrineSearchCriteriaApplicatorInterface
     */
    private $searchCriteriaApplicator;

    /**
     * @var int
     */
    private $contextLanguageId;

    /**
     * @var int
     */
    private $contextShopId;

    /**
     * @var int
     */
    private $contextShopGroupId;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var string
     */
    private $currentGrid;

    private const CASE_BOTH_FIELDS_EXIST = 1;
    private const CASE_ONLY_MIN_FIELD_EXISTS = 2;
    private const CASE_ONLY_MAX_FIELD_EXISTS = 3;

    public function __construct(
        Connection $connection,
        string $dbPrefix,
        DoctrineSearchCriteriaApplicatorInterface $searchCriteriaApplicator,
        int $contextLanguageId,
        int $contextShopId,
        int $contextShopGroupId,
        Configuration $configuration
    ) {
        parent::__construct($connection, $dbPrefix);
        $this->searchCriteriaApplicator = $searchCriteriaApplicator;
        $this->contextLanguageId = $contextLanguageId;
        $this->contextShopId = $contextShopId;
        $this->contextShopGroupId = $contextShopGroupId;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria): QueryBuilder
    {
        $this->currentGrid = $searchCriteria->getFilterId();
        $qb = $this->getQueryBuilder($searchCriteria->getFilters());

        $qb->select('*, id_log as id');

        $this->searchCriteriaApplicator
            ->applyPagination($searchCriteria, $qb)
            ->applySorting($searchCriteria, $qb)
        ;

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria): QueryBuilder
    {
        $qb = $this->getQueryBuilder($searchCriteria->getFilters());
        $qb->select('COUNT(*)');

        return $qb;
    }

    /**
     * Gets query builder.
     *
     * @param array $filterValues
     *
     * @return QueryBuilder
     */
    private function getQueryBuilder(array $filterValues): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->setParameter('id_shop', $this->contextShopId);
        $qb->setParameter('id_lang', $this->contextLanguageId);
        $qb->from($this->dbPrefix . 'api_log_products_ignored');

        foreach ($filterValues as $filterName => $filter) {
            if ('id' === $filterName) {
                $qb->andWhere('id = :id');
                $qb->setParameter('id', $filter);
                continue;
            }

            if ('name' === $filterName) {
                $qb->andWhere('name LIKE :name');
                $qb->setParameter('name', '%' . $filter . '%');
                continue;
            }

            if ('supplier' === $filterName) {
                $qb->andWhere('supplier LIKE :supplier');
                $qb->setParameter('supplier', '%' . $filter . '%');
                continue;
            }

            if ('supplier_code' === $filterName) {
                $qb->andWhere('supplier_code = :supplier_code');
                $qb->setParameter('supplier_code', $filter);
                continue;
            }
        }

        return $qb;
    }
}
