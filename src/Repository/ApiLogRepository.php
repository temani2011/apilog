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

namespace EO\ApiLog\Repository;

use EO\ApiLog\Classes\ApiHandler;
use EO\ApiLog\Model\ApiLogProductsNew;
use EO\ApiLog\Model\ApiLogProductsPrice;
use EO\ApiLog\Model\ApiLogProductsIgnored;
use EO\ApiLog\Model\ApiLogProductsDisabled;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\PrestaShop\Core\Exception\DatabaseException;
use Symfony\Component\Translation\TranslatorInterface;
use Validate;
use DbQuery;
use Db;

/**
 * Class ApiLogRepository.
 */
class ApiLogRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $dbPrefix;

    /**
     * @var array
     */
    private $languages;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ApiLogRepository constructor.
     *
     * @param Connection $connection
     * @param TranslatorInterface $translator
     */
    public function __construct(
        Connection $connection,
        TranslatorInterface $translator
    ) {
        $this->connection = $connection;
        $this->translator = $translator;
    }

    /**
     * Create new product
     *
     * @param int $id
     *
     * @return Product
     */
    public function createProduct(int $id): \Product
    {
        try {
            $productRecord = new ApiLogProductsNew($id);
        } catch (\PrestaShopException $e) {
            throw new \PrestaShopException('Ошибка получения записи', 0, $e);
        }

        if ($productRecord->id !== (int) $id) {
            throw new \PrestaShopException(
                sprintf('Запись c id "%s" не найдено.', $id)
            );
        }

        if ($productRecord->id_product) {
            throw new \PrestaShopException(
                sprintf('Для записи уже cоздан товар с id "%s".', $productRecord->id_product)
            );
        }

        $supplierId = \Supplier::getIdByName($productRecord->supplier);

        if (!$supplierId) {
            throw new \PrestaShopException("Ошибка получения поставщика {$productRecord->supplier}", 0);
        }

        $handler = (new ApiHandler())->createApi($productRecord->supplier);
        $productData = $handler->getProduct($productRecord->supplier_code);
        $newProduct = ApiHandler::createProduct($productData);
        $productRecord->id_product = $newProduct->id;
        $productRecord->save();

        return $newProduct;
    }

    /**
     * Add record to ignore list
     *
     * @param int $id
     *
     * @return ApiLogProductsIgnored
     */
    public function ignoreProduct(int $id): ApiLogProductsIgnored
    {
        try {
            $productRecord = new ApiLogProductsNew($id);
        } catch (\PrestaShopException $e) {
            throw new \PrestaShopException('Ошибка получения записи', 0, $e);
        }

        if ($productRecord->id !== (int) $id) {
            throw new \PrestaShopException(
                sprintf('Запись c id "%s" не найдено.', $id)
            );
        }

        if ($productRecord->id_product) {
            throw new \PrestaShopException(
                sprintf('Для записи уже cоздан товар с id "%s".', $productRecord->id_product)
            );
        }

        $isExists = ApiLogProductsIgnored::getList([
            'select' => 'COUNT(*) as count',
            'where'  => "a.supplier = '{$productRecord->supplier}' AND a.supplier_code = '{$productRecord->supplier_code}'"
        ]);

        $isExists = (int) end($isExists)['count'];

        if ($isExists) {
            throw new \PrestaShopException(
                sprintf('Запись с такими данными уже находится в архиве".')
            );
        }

        $ignoredProduct = new ApiLogProductsIgnored();

        $ignoredProduct->name          = $productRecord->name;
        $ignoredProduct->supplier      = $productRecord->supplier;
        $ignoredProduct->supplier_code = $productRecord->supplier_code;

        if ($ignoredProduct->save()) {
            $productRecord->delete();
        }

        return $ignoredProduct;
    }

    /**
     * Create new product
     *
     * @param int $id
     * @param bool $headers
     *
     * @return resource
     */
    public function exportFeatures(int $id, $headers = true)
    {
        try {
            $productRecord = new ApiLogProductsNew($id);
        } catch (\PrestaShopException $e) {
            throw new \PrestaShopException('Ошибка получения записи', 0, $e);
        }

        if ($productRecord->id !== (int) $id) {
            throw new \PrestaShopException(
                sprintf('Запись c id "%s" не найдено.', $id)
            );
        }

        $handler = (new ApiHandler())->createApi($productRecord->supplier);
        $features = $handler->getFeatures($productRecord->supplier_code);

        if (!$features) {
            throw new \PrestaShopException(
                sprintf('Ошибка при получении характеристик - данные пусты.', $id)
            );
        }

        $file = fopen('php://output', 'w');

        if ($headers) {
            fputcsv($file, [
                'Поставщик',
                'Код товара поставщика',
                'ID товара',
                'Название',
                'Артикул',
                'Характеристика',
                'Значение',
            ]);
        }

        foreach ($features as $feature) {
            fputcsv($file, [
                $productRecord->supplier,
                $productRecord->supplier_code,
                $productRecord->id_product,
                $productRecord->name,
                $productRecord->reference,
                $feature['name'],
                $feature['value'],
            ]);
        }

        return;
    }

    /**
     * Create new products
     *
     * @param array $ids
     *
     * @return array
     */
    public function createProductBulk(array $ids): array
    {
        $products = $errors = [];

        foreach ($ids as $id) {
            try {
                $products[] = $this->createProduct($id);
            } catch (\PrestaShopException $e) {
                $errors[] = "[{$id}]: " . $e->getMessage();
            }
        }

        return [
            'products' => $products,
            'errors'   => $errors,
        ];
    }

    /**
     * Ignore products
     *
     * @param array $ids
     *
     * @return array
     */
    public function ignoreProductBulk(array $ids): array
    {
        $products = $errors = [];

        foreach ($ids as $id) {
            try {
                $products[] = $this->ignoreProduct($id);
            } catch (\PrestaShopException $e) {
                $errors[] = "[{$id}]: " . $e->getMessage();
            }
        }

        return [
            'products' => $products,
            'errors'   => $errors,
        ];
    }

    /**
     * Export features products
     *
     * @param array $ids
     *
     * @return void
     */
    public function exportFeaturesBulk(array $ids)
    {
        $errors = [];

        foreach ($ids as $key => $id) {
            try {
                $this->exportFeatures($id, $key > 0 ? false : true);
            } catch (\PrestaShopException $e) {
                $errors[] = "[{$id}]: " . $e->getMessage();
            }
        }

        return [
            'errors' => $errors,
        ];
    }

    /**
     * Restore records from ignore list
     *
     * @param array $ids
     *
     * @return array
     */
    public function restoreProducts(array $ids): array
    {
        $products = $errors = [];

        foreach ($ids as $id) {
            try {
                $products[] = $this->restoreProduct($id);
            } catch (\PrestaShopException $e) {
                $errors[] = "[{$id}]: " . $e->getMessage();
            }
        }

        return [
            'products' => $products,
            'errors'   => $errors,
        ];
    }

    /**
     * Restore record from ignore list
     *
     * @param int $id
     *
     * @return ApiLogProductsNew
     */
    public function restoreProduct(int $id): ApiLogProductsNew
    {
        try {
            $ignoredRecord = new ApiLogProductsIgnored($id);
        } catch (\PrestaShopException $e) {
            throw new \PrestaShopException('Ошибка получения записи', 0, $e);
        }

        if ($ignoredRecord->id !== (int) $id) {
            throw new \PrestaShopException(
                sprintf('Запись c id "%s" не найдено.', $id)
            );
        }

        $handler = (new ApiHandler())->createApi($ignoredRecord->supplier);
        $product = $handler->getProduct($ignoredRecord->supplier_code);

        $productRecord = new ApiLogProductsNew();

        $productRecord->name            = $product['name'];
        $productRecord->supplier        = $ignoredRecord->supplier;
        $productRecord->supplier_code   = $ignoredRecord->supplier_code;
        $productRecord->reference       = $product['reference'];
        $productRecord->color           = $product['color'];
        $productRecord->base_price      = $product['price'] ? $product['price']['base_price'] : 0;
        $productRecord->recommend_price = $product['price'] ? $product['price']['recommend_price'] : 0;
        $productRecord->multiplicity    = 1; //?
        $productRecord->min_pack        = $product['minimal_quantity'];

        if ($productRecord->save()) {
            $ignoredRecord->delete();
        }

        return $productRecord;
    }

    /**
     * Activate products
     *
     * @param array $ids
     *
     * @return array
     */
    public function activateProducts(array $ids): array
    {
        $products = $errors = [];

        foreach ($ids as $id) {
            try {
                $products[] = $this->activateProduct($id);
            } catch (\PrestaShopException $e) {
                $errors[] = "[{$id}]: " . $e->getMessage();
            }
        }

        return [
            'products' => $products,
            'errors'   => $errors,
        ];
    }

    /**
     * Activate product
     *
     * @param int $id
     *
     * @return Product
     */
    public function activateProduct(int $id): \Product
    {
        try {
            $productRecord = new ApiLogProductsDisabled($id);
        } catch (\PrestaShopException $e) {
            throw new \PrestaShopException('Ошибка получения записи', 0, $e);
        }

        if ($productRecord->id !== (int) $id) {
            throw new \PrestaShopException(
                sprintf('Запись c id "%s" не найдено.', $id)
            );
        }

        if ($productRecord->status == 1) {
            $isExists = \Product::getList([
                'select' => 'COUNT(*) as count',
                'where' => "a.id_product = '{$productRecord->id_product}'"
            ]);

            $isExists = (int) end($isExists)['count'];

            if (!$isExists) {
                throw new \PrestaShopException(
                    sprintf('Товара с id "' . $productRecord->id_product . '" не существует.')
                );
            }

            $priceProduct = new ApiLogProductsPrice();

            $priceProduct->id_product      = $productRecord->id_product;
            $priceProduct->base_price      = $productRecord->base_price;
            $priceProduct->recommend_price = $productRecord->recommend_price;
            $priceProduct->date_add        = $productRecord->date_add;

            if ($priceProduct->save()) {
                $productRecord->delete();
            }

            return new \Product($priceProduct->id_product);
        }

        $product = new \Product($productRecord->id_product);

        $product->available = 1;
        $product->available_for_order = 1;
        $product->active = 1;

        if ($product->save()) {
            $productRecord->delete();
        }

        return $product;
    }
}
