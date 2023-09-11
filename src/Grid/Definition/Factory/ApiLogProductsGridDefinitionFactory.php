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
use PrestaShopBundle\Form\Admin\Type\NumberMinMaxFilterType;

/**
 * Class ApiLogProductsGridDefinitionFactory.
 */
final class ApiLogProductsGridDefinitionFactory extends AbstractGridDefinitionFactory
{
    const GRID_ID = 'api_log_products';

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
        return 'Новые товары';
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
                (new DataColumn('supplier_code'))
                    ->setName('Код поставщика')
                    ->setOptions([
                        'field' => 'supplier_code',
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
                (new DataColumn('base_price'))
                    ->setName('Закупка')
                    ->setOptions([
                        'field' => 'base_price',
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
                (new DataColumn('multiplicity'))
                    ->setName('Кратность')
                    ->setOptions([
                        'field' => 'multiplicity',
                    ])
            )
            ->add(
                (new DataColumn('min_pack'))
                    ->setName('Мин. партия')
                    ->setOptions([
                        'field' => 'min_pack',
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
                    ->setOptions([
                        'actions' => (new RowActionCollection())
                            ->add((new SubmitRowAction('create'))
                                ->setIcon('add')
                                ->setName('Создать товар')
                                ->setOptions([
                                    'method'            => 'POST',
                                    'route'             => 'admin_api_log_products_create',
                                    'route_param_name'  => 'id',
                                    'route_param_field' => 'id',
                                    'confirm_message'   => 'Создать товар?',

                            ]))
                            ->add((new SubmitRowAction('archive'))
                                ->setIcon('create')
                                ->setName('Добавить в архив')
                                ->setOptions([
                                    'method'            => 'POST',
                                    'route'             => 'admin_api_log_products_ignore',
                                    'route_param_name'  => 'id',
                                    'route_param_field' => 'id',
                                    'confirm_message'   => 'Добавить запись в архив?',

                            ]))
                            ->add((new SubmitRowAction('export_features'))
                                ->setIcon('cloud_download')
                                ->setName('Выгрузить характеристики')
                                ->setOptions([
                                    'method'            => 'POST',
                                    'route'             => 'admin_api_log_products_export_features',
                                    'route_param_name'  => 'id',
                                    'route_param_field' => 'id',
                            ]))
                        ,
                    ])
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
                (new Filter('supplier_code', TextType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Код поставщика',
                        ],
                    ])
                    ->setAssociatedColumn('supplier_code')
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
                (new Filter('multiplicity', NumberType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Кратность',
                        ],
                    ])
                    ->setAssociatedColumn('multiplicity')
            )
            ->add(
                (new Filter('min_pack', NumberType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => 'Мин. партия',
                        ],
                    ])
                    ->setAssociatedColumn('min_pack')
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
                        'redirect_route' => 'admin_api_log_products_list',
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
        return (new BulkActionCollection())
            ->add(
                (new SubmitBulkAction('create_selection'))
                    ->setName('Создать товары')
                    ->setOptions([
                        'submit_route' => 'admin_api_log_products_bulk_create',
                    ])
            )
            ->add(
                (new SubmitBulkAction('ignore_selection'))
                    ->setName('Добавить товары в архив')
                    ->setOptions([
                        'submit_route' => 'admin_api_log_products_bulk_export_features',
                    ])
            )
            ->add(
                (new SubmitBulkAction('export_selection'))
                    ->setName('Выгрузить характеристики товаров')
                    ->setOptions([
                        'submit_route' => 'admin_api_log_products_bulk_export_features',
                    ])
            )
        ;
    }
}
