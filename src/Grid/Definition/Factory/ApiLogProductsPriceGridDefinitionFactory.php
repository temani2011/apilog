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

namespace EO\ApiLog\Grid\Definition\Factory;

use PrestaShopBundle\Form\Admin\Type\DateRangeType;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollection;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use PrestaShopBundle\Form\Admin\Type\SearchAndResetType;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ToggleColumn;
use PrestaShopBundle\Form\Admin\Type\YesAndNoChoiceType;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\DateTimeColumn;
use Symfony\Component\Validator\Constraints as Assert;
use PrestaShop\PrestaShop\Core\Grid\Action\Bulk\BulkActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Bulk\Type\SubmitBulkAction;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\BulkActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\SubmitRowAction;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use PrestaShopBundle\Form\Admin\Type\NumberMinMaxFilterType;

/**
 * Class ApiLogProductsPriceGridDefinitionFactory.
 */
final class ApiLogProductsPriceGridDefinitionFactory extends AbstractGridDefinitionFactory
{
    const GRID_ID = 'api_log_products_price';

    /**
     * {@inheritdoc}
     */
    protected function getId()
    {
        return self::GRID_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getName()
    {
        return 'Изменения в цене';
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumns()
    {
        return (new ColumnCollection())
            ->add(
                (new BulkActionColumn('bulk'))
                    ->setOptions([
                        'bulk_field' => 'id',
                    ])
            )
            ->add(
                (new DataColumn('id_log'))
                    ->setName('ID')
                    ->setOptions([
                        'field' => 'id_log',
                    ])
            )
            ->add(
                (new DataColumn('id_product'))
                    ->setName('ID Товара')
                    ->setOptions([
                        'field' => 'id_product',
                    ])
            )
            ->add(
                (new DataColumn('supplier'))
                    ->setName('Поставщик')
                    ->setOptions([
                        'field' => 'supplier',
                    ])
            )
            ->add(
                (new DataColumn('name'))
                    ->setName('Название')
                    ->setOptions([
                        'field' => 'name',
                    ])
            )
            ->add(
                (new DataColumn('reference'))
                    ->setName('Артикул')
                    ->setOptions([
                        'field' => 'reference',
                    ])
            )
            ->add(
                (new DataColumn('color'))
                    ->setName('Цвет')
                    ->setOptions([
                        'field' => 'color',
                    ])
            )
            ->add(
                (new DataColumn('category'))
                    ->setName('Категория')
                    ->setOptions([
                        'field' => 'category',
                    ])
            )
            ->add(
                (new DataColumn('base_price'))
                    ->setName('Закупка')
                    ->setOptions([
                        'field' => 'base_price',
                    ])
            )
            ->add(
                (new DataColumn('base_price_current'))
                    ->setName('Текущая закупка')
                    ->setOptions([
                        'field' => 'base_price_current',
                    ])
            )
            ->add(
                (new DataColumn('recommend_price'))
                    ->setName('РРЦ')
                    ->setOptions([
                        'field' => 'recommend_price',
                    ])
            )
            ->add(
                (new DataColumn('recommend_price_current'))
                    ->setName('Текущая РРЦ')
                    ->setOptions([
                        'field' => 'recommend_price_current',
                    ])
            )
            ->add(
                (new DataColumn('visibility'))
                    ->setName('Видимость')
                    ->setOptions([
                        'field' => 'visibility',
                    ])
            )
            ->add(
                (new DateTimeColumn('date_add'))
                    ->setName('Дата')
                    ->setOptions([
                        'format' => 'Y-m-d H:i',
                        'field' => 'date_add',
                    ])
            )
            ->add(
                (new ActionColumn('actions'))
                    ->setName($this->trans('Actions', [], 'Admin.Global'))
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFilters()
    {
        return (new FilterCollection())
            ->add(
                (new Filter('id_log', TextType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'ID',
                        ],
                    ])
                    ->setAssociatedColumn('id_log')
            )
            ->add(
                (new Filter('id_product', TextType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'ID товара',
                        ],
                    ])
                    ->setAssociatedColumn('id_product')
            )
            ->add(
                (new Filter('supplier', TextType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Поставщик',
                        ],
                    ])
                    ->setAssociatedColumn('supplier')
            )
            ->add(
                (new Filter('name', TextType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Название',
                        ],
                    ])
                    ->setAssociatedColumn('name')
            )
            ->add(
                (new Filter('reference', TextType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Артикул',
                        ],
                    ])
                    ->setAssociatedColumn('reference')
            )
            ->add(
                (new Filter('color', NumberType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Цвет',
                        ],
                    ])
                    ->setAssociatedColumn('color')
            )
            ->add(
                (new Filter('category', TextType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Категория',
                        ],
                    ])
                    ->setAssociatedColumn('category')
            )
            ->add(
                (new Filter('base_price', NumberMinMaxFilterType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Закупка',
                        ],
                    ])
                    ->setAssociatedColumn('base_price')
            )
            ->add(
                (new Filter('recommend_price', NumberMinMaxFilterType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'РРЦ',
                        ],
                    ])
                    ->setAssociatedColumn('recommend_price')
            )
            ->add(
                (new Filter('visibility', ChoiceType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'choices' => [
                            $this->translator->trans('Everywhere', [], 'Admin.Catalog.Feature') => 'both',
                            $this->translator->trans('Catalog only', [], 'Admin.Catalog.Feature') => 'catalog',
                            $this->translator->trans('Search only', [], 'Admin.Catalog.Feature') => 'search',
                            'Только серия' => 'series',
                            $this->translator->trans('Nowhere', [], 'Admin.Catalog.Feature') => 'none',
                        ],
                    ])
                    ->setAssociatedColumn('visibility')
            )
            ->add(
                (new Filter('date_add', DateRangeType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Дата',
                        ],
                    ])
                    ->setAssociatedColumn('date_add')
            )
            ->add(
                (new Filter('actions', SearchAndResetType::class))
                    ->setAssociatedColumn('actions')
                    ->setTypeOptions([
                        'reset_route' => 'admin_common_reset_search_by_filter_id',
                        'reset_route_params' => [
                            'filterId' => self::GRID_ID,
                        ],
                        'redirect_route' => 'admin_api_log_products_price_list',
                    ])
                    ->setAssociatedColumn('actions')
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function getBulkActions()
    {
        return (new BulkActionCollection());
    }
}
