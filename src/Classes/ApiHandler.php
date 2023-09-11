<?php

namespace EO\ApiLog\Classes;

use Db;
use Shop;
use Tools;
use Price;
use Image;
use Product;
use Feature;
use Validate;
use Attribute;
use Combination;
use FeatureValue;
use Manufacturer;
use EO\ApiLog\Classes\Api\MerlionApi;
use EO\ApiLog\Classes\Api\RelefApi;
use EO\ApiLog\Classes\Interfaces\Api;
use EO\ApiLog\Classes\Interfaces\ApiFactory;

class ApiHandler implements ApiFactory
{
    public function createApi(string $supplier): Api
    {
        switch ($supplier) {
            case 'Мерлион':
                return new MerlionApi();
                break;
            case 'Рельеф':
                return new RelefApi();
                break;
        }
    }

    public static function createProduct(array $productData): Product
    {
        $product = new Product;

        if (!$productData
            || !isset($productData['images'])
            || !isset($productData['price'])
            || !$productData['images']
            || !$productData['price']
        ) {
            throw new \PrestaShopException(
                sprintf('Не хватает данных для создания товара.')
            );
        }

        self::fillProduct($product, $productData);

        $product->save();

        self::createPrice($product, $productData['price']);
        self::createCombination($product, $productData);
        self::createAdditionalData($product, $productData);
        self::createImages($product, $productData['images']);
        self::createFeatures($product, $productData['features']);

        return $product;
    }

    private function createFeatures(Product $product, array $features)
    {
        foreach ($features as $feature) {
            $name = str_replace(
                ['\\', '"', '^', '<', '>', '=', '{', '}'],
                '',
                mb_substr($feature['name'], 0, 124)
            );
            $value = str_replace(
                ['\\', '"', '^', '<', '>', '=', '{', '}'],
                '',
                preg_replace('/\s+/', ' ', mb_substr($feature['name'], 0, 254))
            );

            if (!empty($name) && !empty($value)) {
                $featureId = (int) Feature::getFeature(1, $name)['id_feature'];

                if (!$featureId) {
                    $feature = new Feature(null, 1, 2);
                    $feature->name = $name;
                    $feature->active = 1;
                    $feature->add();
                    $featureId = $feature->id;
                }

                $featureValueId = (int) FeatureValue::getFeatureValueIdByFeatureIdAndValue($featureId, $value);

                if (!$featureValueId) {
                    $featureValue = new FeatureValue(null, 1, 2);
                    $featureValue->id_feature = $featureId;
                    $featureValue->value = $value;
                    $featureValue->custom = 0;
                    $featureValue->add();
                    $featureValueId = $featureValue->id;
                }

                if (!Product::isFeatureValueLinked($product->id, $featureId, $featureValueId)) {
                    Db::getInstance()->insert('feature_product', [
                        'id_feature'       => $featureId,
                        'id_product'       => $product->id,
                        'id_feature_value' => $featureValueId
                    ]);
                }
            }
        }
    }

    private function createImages(Product $product, array $images)
    {
        if (!file_exists(_PS_ROOT_DIR_ . '/tmp')) {
            mkdir(_PS_ROOT_DIR_ . '/tmp', 0777, true);
        }

        foreach ($images as $key => $image) {
            $temp_file = _PS_ROOT_DIR_ . '/tmp/temp-' . $product->id . '.jpg';
            $ch = curl_init($image);
            $fp = fopen($temp_file, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);

            $im = new Image(null, 1);
            $im->id_product = $product->id;
            $im->legend = mb_substr(str_replace('"', '', $product->name), 0, 127);
            $im->cover = $key > 0 ? 0 : 1;
            $im->add();
            $new_file = $im->getPathForCreation() . '.jpg';

            if (!is_dir(dirname($new_file))) {
                mkdir(dirname($new_file), 0777, true);
            }

            foreach (glob(dirname($new_file) . '/*') as $file) {
                unlink($file);
            }

            if (copy($temp_file, $new_file)) {
                chmod($new_file, 0777);
                unlink($temp_file);
            }
        }
    }

    private function createAdditionalData(Product $product, array $data)
    {
        // Разделы
        Db::getInstance()->insert('category_product', [
            'id_category' => $data['category_id'],
            'id_product'  => $product->id,
            'position'    => 1
        ]);

        // Кода остатков
        Db::getInstance()->insert('product_supplier', [
            'id_product'                 => $product->id,
            'id_product_attribute'       => $product->id_product_attribute,
            'id_supplier'                => $data['id'],
            'product_supplier_reference' => $data['supplier_code'],
            'product_supplier_price_te'  => 0.000000,
            'id_currency'                => 1,
            'imported'                   => 0,
        ]);

        // Остатки
        Db::getInstance()->insert('stock', [
            'id_warehouse'         => 5,
            'id_product'           => $product->id,
            'id_product_attribute' => $product->id_product_attribute,
            'reference'            => $data['supplier_code'],
            'ean13'                => null,
            'isbn'                 => null,
            'upc'                  => null,
            'physical_quantity'    => $data['stock'],
            'usable_quantity'      => $data['stock'],
            'price_te'             => null,
            'updated'              => 0,
        ]);

        // Лог
        Db::getInstance()->insert('multiplicity', [
            'id_product'       => $product->id,
            'id_supplier'      => $product->id_supplier,
            'supplier_code'    => $data['supplier_code'],
            'name'             => str_replace("'", '', $product->name[1]),
            'multiplicity'     => 0,
            'multiplicity_set' => 0,
            'barcodes'         => '',
            'barcode'          => '',
            'description'      => '',
            'status'           => 0,
            'date_upd'         => date('Y-d-m H:i:s'),
        ]);
    }

    private function createCombination(Product $product)
    {
        if ((!$product->id_product_attribute || is_null($product->id_product_attribute))
            && (int) $product->id_attribute > 0
        ) {
            $c = new Combination();
            $c->id_product = $product->id;
            $c->default_on = 1;
            $c->add();

            $c->setAttributes([$product->id_attribute]);

            $product->id_product_attribute = $c->id;
            $product->update();
        }
    }

    private function createPrice(Product $product, array $priceData)
    {
        $price = new Price($product->id, 1);
        $price->id_product = $product->id;
        $price->id_currency = 1;
        $price->base_price = round($priceData['base_price'], 9);
        $price->recommend_price = round($priceData['recommend_price'], 9);
        $price->add();
    }

    private function fillProduct(Product &$product, array &$data)
    {
        $productName = preg_replace(Tools::cleanNonUnicodeSupport('/[<>;=#{}]*/u'), '', $data['name']);
        $productReference = preg_replace(Tools::cleanNonUnicodeSupport('/[<>;=#{}]*/u'), '', $data['reference']);

        if (!Validate::isCatalogName($productName)) {
            throw new \PrestaShopException(
                sprintf('Не хватает данных для создания товара.')
            );
        }

        // Общие данные
        $product->name = mb_substr($productName, 0, 254);
        $product->link_rewrite = mb_substr(Tools::str2url($productName), 0, 127);
        $product->reference = $productReference;
        $product->active = 0;
        $product->id_supplier = $data['id'];
        $product->id_shop_default = 1;
        $product->visibility = 'both';
        $product->available = 1;
        $product->available_for_order = 1;
        $product->show_price = 1;
        $product->assembly = 0;

        $product->id_shop_list = array_column(Shop::getList([
            'select' => 'id_shop',
            'where'  => 'a.id_shop_group = 1 AND a.active = 1'
        ]), 'id_shop');

        // Разделы
        $product->id_category_default = $data['category_id'];

        // Упаковка
        $product->height = round($data['height'], 9);
        $product->width  = round($data['width'], 9);
        $product->depth  = round($data['length'], 9);
        $product->weight = round($data['weight'], 9);
        $product->volume = round($data['volume'], 9);

        // Мин. кол-во
        if (isset($data['limit_type']) && $data['limit_type'] == 'Кратно' && $data['minimal_quantity'] > 0) { // Мерлион
            $product->minimal_quantity = $data['minimal_quantity'];
        } elseif (isset($data['minimal_quantity']) && $data['minimal_quantity'] > 0) {
            $product->minimal_quantity = $data['minimal_quantity'];
        }

        // Цвет
        if (isset($data['color']) && $data['color']) {
            self::fillColor($product, [
                'name'    => $data['color'],
                'groupId' => $data['attribute_group'],
            ]);
        }

        // Бренд
        if (isset($data['brand']) && $data['brand']) {
            self::fillBrand($product, $data['brand']);
        }

        // Бренд
        if (isset($data['brand']) && $data['brand']) {
            self::fillBrand($product, $data['brand']);
        }

        $product->date_add = date('Y-d-m H:i:s');
        $product->date_upd = date('Y-d-m H:i:s');

        $product->add();
    }

    private function fillColor(Product &$product, array $colorData)
    {
        $color = Tools::mb_ucfirst(mb_strtolower($colorData['name']));
        $color = preg_replace(Tools::cleanNonUnicodeSupport('/[<>;=#{}]*/u'), '', $color);

        $attributeId = Attribute::getAttributeIdByGroupIdAndName($colorData['groupId'], $color);

        if (!$attributeId) {
            $attribute = new Attribute(null, 1);
            $attribute->name = $color;
            $attribute->id_attribute_group = $colorData['groupId'];
            $attribute->position = 1;
            $attribute->add();
            $attributeId = $attribute->id;
        }

        $product->id_attribute = $attributeId;
    }

    private function fillBrand(Product &$product, string $brand)
    {
        $vendor = Tools::mb_ucfirst(mb_strtolower($brand));
        $vendor = preg_replace(Tools::cleanNonUnicodeSupport('/[<>;=#{}]*/u'), '', $vendor);

        $manufacturerId = Manufacturer::getIdByName($vendor);

        if (!$manufacturerId) {
            $manufacturer = new Manufacturer(null, 1);
            $manufacturer->name = $vendor;
            $manufacturer->date_add = date('Y-d-m H:i:s');
            $manufacturer->date_upd = date('Y-d-m H:i:s');
            $manufacturer->link_rewrite = mb_substr(Tools::str2url($vendor), 0, 127);
            $manufacturer->active = 1;
            $manufacturer->add();
            $manufacturerId = $manufacturer->id;
        }

        $product->id_manufacturer = $manufacturerId;
    }
}
