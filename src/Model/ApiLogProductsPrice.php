<?php
/**
 * 2007-2019 PrestaShop and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace EO\ApiLog\Model;

use Db;
use DbQuery;

class ApiLogProductsPrice extends \ObjectModel
{
    /** @var int */
    public $id_log;

    /** @var int */
    public $id_product;

    /** @var float */
    public $base_price;

    /** @var float */
    public $recommend_price;

    /** @var float */
    public $date_add;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table'               => 'api_log_products_price',
        'primary'             => 'id_log',
        'multilang'           => false,
        'fields'              => array(
            'id_product'      => array('type' => self::TYPE_INT,   'validate' => 'isUnsignedInt'),
            'base_price'      => array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
            'recommend_price' => array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
            'date_add'        => array('type' => self::TYPE_DATE,  'validate' => 'isDate'),
        ),
    );

    /**
     * {@inheritdoc}
     */
    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    /**
     * {@inheritdoc}
     */
    public function add($auto_date = true, $null_values = false)
    {
        return parent::add($auto_date, $null_values);
    }

    /**
     * @return void
     */
    public function toArray()
    {
        return [
            'id_log'          => $this->id_log,
            'id_product'      => $this->id_product,
            'base_price'      => $this->base_price,
            'recommend_price' => $this->recommend_price,
            'date_add'        => $this->date_add,
        ];
    }
}
