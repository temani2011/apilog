<?php

namespace EO\ApiLog\Classes\Api;

use Exception;
use SoapClient;
use EO\ApiLog\Classes\Interfaces\Api;

class RelefApi implements Api
{
    protected $id               = 123123123;
    protected $name             = 'Рельеф';
    protected $categoryId       = 234234234;
    protected $attributeGroupId = 123123123;
    protected $curlOptions      = null;

    public function __construct()
    {
        $this->curlOptions = [
            CURLOPT_URL            => 'https://api-sale.relef.ru/api/v1/products/list',
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POST           => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => [
                "Accept: */*",
                "Connection: Keep-Alive",
                "apikey: asdfsadfasdfasdfasdf",
            ],
        ];
    }

    public function getFeatures(string $id)
    {
        $details = $this->requestProduct($id);

        $featuresData = $this->getFeaturesData($details);

        return $featuresData;
    }

    public function getProduct(string $id)
    {
        $productData = $this->requestProduct($id);

        if (!$productData) {
            return [];
        }

        $product = [
            'id'              => (int)    $this->id,
            'category_id'     => (int)    $this->categoryId,
            'supplier'        => (string) $this->name,
            'attribute_group' => (string) $this->attributeGroupId,
            'supplier_code'   => (string) $productData['code'],                 // код товара
            'reference'       => (string) $productData['vendorCode'],           // партномер
            'name'            => (string) $productData['name'],                 // наименование товара
            'brand'           => (string) $productData['manufacturer']['name'], // бренд
            'description'     => (string) $productData['description'],          // описание товара
        ];

        $packData     = $this->getPackData($productData);
        $featuresData = $this->getFeaturesData($productData);
        $priceData    = $this->getPriceData($productData);
        $imagesData   = $this->getImagesData($productData);

        $colorKey = array_search('Цвет', array_column($featuresData, 'name'));

        if (isset($featuresData[$colorKey])) {
            $product['color'] = $featuresData[$colorKey]['value'];
        }

        $product = array_merge($product, $packData);

        $product['price']    = $priceData;
        $product['images']   = $imagesData;
        $product['features'] = $featuresData;

        return $product;
    }

    private function getImagesData(array $data)
    {
        if (!isset($data['images'])) {
            return [];
        }

        $imagesData = [];

        foreach ($data['images'] as $image) {
            $imagesData[] = $image['path'];
        }

        return $imagesData;
    }

    private function getPriceData(array $data)
    {
        if (!isset($data['prices'])) {
            return [];
        }

        $priceData = [];

        foreach ($data['prices'] as $price) {
            if ($price['type'] == 'recommend') {
                $priceData['recommend_price'] = (float) $price['value'];
            }
            if ($price['type'] == 'contracts') {
                $priceData['base_price'] = (float) $price['value'];
            }
        }

        return $priceData;
    }

    private function getFeaturesData(array $data)
    {
        if (!isset($data['properties'])) {
            return [];
        }

        $featuresData = [];

        foreach ($data['properties'] as $key => $feature) {
            $featuresData[] = [
                'id'    => (string) $feature['code'],
                'name'  => (string) $feature['name'],
                'value' => (string) $feature['value'],
            ];
        }

        return $featuresData;
    }

    private function getPackData(array $data)
    {
        if (!isset($data['typePackages']) || !isset($data['packUnits'])) {
            return [];
        }

        $packKey  = array_search('min', array_column($data['typePackages'], 'type'));
        $packRate = isset($data['typePackages'][$packKey]) ? $data['typePackages'][$packKey]['rate'] : 0;
        $packData = [];

        foreach ($data['packUnits'] as $key => $packUnit) {
            if ($packUnit['rate'] == 1) {
                $packData['volume'] = (float) $packUnit['volume'];
                $packData['weight'] = (float) $packUnit['weight'];
                $packData['length'] = (float) $packUnit['length'];
                $packData['width']  = (float) $packUnit['width'];
                $packData['height'] = (float) $packUnit['height'];
            }

            if ($packRate && $packUnit['rate'] == $packRate) {
                $packData['barcode']          = (string) $packUnit['barcode'];
                $packData['minimal_quantity'] = (int)    $packRate;
            }
        }

        $packData['stock'] = 0;

        if (!isset($data['remains'])) {
            return $packData;
        }

        foreach ($data['remains'] as $remain) {
            if ($remain['code'] != 'ASF') {
                continue;
            }

            $packData['stock'] = (int) $remain['quantity'];
        }

        return $packData;
    }

    private function requestProduct(string $code)
    {
        $params = [
            'filter' => ['code' => [$code]],
            'limit'  => 1,
            'offset' => 0
        ];

        $this->curlOptions[CURLOPT_POSTFIELDS] = json_encode($params, JSON_UNESCAPED_UNICODE);

        $curl = curl_init();
        curl_setopt_array($curl, $this->curlOptions);
        $result = json_decode(curl_exec($curl), true);

        if (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200) {
            throw new Exception('Error code: ' . curl_getinfo($curl, CURLINFO_HTTP_CODE));
        }

        curl_close($curl);

        return $result['count'] > 0 ? end($result['list']): [];
    }
}
