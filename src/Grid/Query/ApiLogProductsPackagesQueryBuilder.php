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
 * Class ApiLogProductsPackagesQueryBuilder.
 */
final class ApiLogProductsPackagesQueryBuilder extends AbstractDoctrineQueryBuilder
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

        $qb->select('alpp.*, id_log as id');
        $qb->addSelect('s.name as supplier');
        $qb->addSelect('pl.name');
        $qb->addSelect('p.reference');
        $qb->addSelect('al.name as color');
        $qb->addSelect('cl.name as category');

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
        $qb->from($this->dbPrefix . 'api_log_products_packages', 'alpp');
        $qb->leftJoin('alpp', $this->dbPrefix . 'product', 'p', 'alpp.id_product = p.id_product');
        $qb->leftJoin('p', $this->dbPrefix . 'supplier', 's', 'p.id_supplier = s.id_supplier');
        $qb->leftJoin('p', $this->dbPrefix . 'product_lang', 'pl', 'pl.id_product = p.id_product AND pl.id_shop = :id_shop');
        $qb->leftJoin('p', $this->dbPrefix . 'product_shop', 'ps', 'ps.id_product = p.id_product');
        $qb->leftJoin('pl', $this->dbPrefix . 'attribute_lang', 'al', 'al.id_attribute = p.id_attribute');
        $qb->leftJoin('ps', $this->dbPrefix . 'category_lang', 'cl', 'ps.id_category_default = cl.id_category AND cl.id_shop = :id_shop');
        $qb->where('ps.id_shop = :id_shop');

        foreach ($filterValues as $filterName => $filter) {
            if ('id_log' === $filterName) {
                $qb->andWhere('id_log = :id_log');
                $qb->setParameter('id_log', $filter);
                continue;
            }

            if ('id_product' === $filterName) {
                $qb->andWhere('alpp.id_product = :id_product');
                $qb->setParameter('id_product', $filter);
                continue;
            }

            if ('id_package' === $filterName) {
                $qb->andWhere('alpp.id_package = :id_package');
                $qb->setParameter('id_package', $filter);
                continue;
            }

            if ('supplier' === $filterName) {
                $qb->andWhere('s.name LIKE :supplier');
                $qb->setParameter('supplier', '%' . $filter . '%');
                continue;
            }

            if ('name' === $filterName) {
                $qb->andWhere('pl.name LIKE :name');
                $qb->setParameter('name', '%' . $filter . '%');
                continue;
            }

            if ('reference' === $filterName) {
                $qb->andWhere('p.reference = :reference');
                $qb->setParameter('reference', $filter);
                continue;
            }

            if ('color' === $filterName) {
                $qb->andWhere('al.name LIKE :color');
                $qb->setParameter('color', '%' . $filter . '%');
                continue;
            }

            if ('category' === $filterName) {
                $qb->andWhere('al.name LIKE :category');
                $qb->setParameter('category', $filter);
                continue;
            }

            if ('date_add' === $filterName) {
                $minFieldSqlCondition = sprintf('%s >= :%s_min', 'alpp.' . $filterName, $filterName);
                $maxFieldSqlCondition = sprintf('%s <= :%s_max', 'alpp.' . $filterName, $filterName);

                switch ($this->computeMinMaxCase($filter, ['from', 'to'])) {
                    case self::CASE_BOTH_FIELDS_EXIST:
                        $qb->andWhere(sprintf('%s AND %s', $minFieldSqlCondition, $maxFieldSqlCondition));
                        $qb->setParameter(sprintf('%s_min', $filterName), $filter['from']);
                        $qb->setParameter(sprintf('%s_max', $filterName), $filter['to']);
                        break;
                    case self::CASE_ONLY_MIN_FIELD_EXISTS:
                        $qb->andWhere($minFieldSqlCondition);
                        $qb->setParameter(sprintf('%s_min', $filterName), $filter['from']);
                        break;
                    case self::CASE_ONLY_MAX_FIELD_EXISTS:
                        $qb->andWhere($maxFieldSqlCondition);
                        $qb->setParameter(sprintf('%s_max', $filterName), $filter['to']);
                        break;
                }
                continue;
            }
        }

        return $qb;
    }

    /**
     * @param array<string, int> $value
     *
     * @return int
     */
    private function computeMinMaxCase(array $value, array $keys): int
    {
        $minFieldExists = isset($value[$keys[0]]);
        $maxFieldExists = isset($value[$keys[1]]);

        if ($minFieldExists && $maxFieldExists) {
            return self::CASE_BOTH_FIELDS_EXIST;
        }
        if ($minFieldExists) {
            return self::CASE_ONLY_MIN_FIELD_EXISTS;
        }

        if ($maxFieldExists) {
            return self::CASE_ONLY_MAX_FIELD_EXISTS;
        }

        throw new Exception('Min max filter wasn\'t applied correctly');
    }
}
