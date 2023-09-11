<?php

namespace EO\ApiLog\Classes\Api;

use Exception;
use SoapClient;
use EO\ApiLog\Classes\Interfaces\Api;

class MerlionApi implements Api
{
    protected $id                  = 123123;
    protected $name                = 'Мерлион';
    protected $categoryId          = 123123;
    protected $attributeGroupId    = 123123;
    protected $soapClient          = null;
    protected $featureIdsToExclude = [
        14532,  // PartNumber/Артикул Производителя
        513721, // Длина упаковки
        513722, // Ширина упаковки
        513723, // Высота упаковки
        513724, // Габариты упаковки
        513725, // Вес упаковки
    ];

    public function __construct()
    {
        try {
            $this->soapClient = new SoapClient("https://api.merlion.com/dl/mlservice3?wsdl", [
                'login'    => "asdfasdfasd|API",
                'password' => "asdfasdfasdfsa",
                'encoding' => "UTF-8",
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS
            ]);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    public function getFeatures(string $id)
    {
        $response = $this->soapClient->getItemsProperties((object) ['item_id' => [$id]]);

        if (!$response->getItemsPropertiesResult) {
            return [];
        }

        $features = [];

        foreach ($response->getItemsPropertiesResult->item as $key => $feature) {
            if (in_array($feature->PropertyID, $this->featureIdsToExclude)) {
                continue;
            }

            $features[$key] = [
                'id'    => (string) $feature->PropertyID,
                'name'  => (string) $feature->PropertyName,
                'value' => (string) $feature->Value,
            ];
        }

        return $features;
    }

    public function getPrice(string $id)
    {
        $response = $this->soapClient->getItemsAvail((object) ['item_id' => [$id]]);

        if (!$response->getItemsAvailResult) {
            return [];
        }

        $price = end($response->getItemsAvailResult->item);

        return [
            'stock'           => (int)   $price->AvailableClient,
            'base_price'      => (float) $price->PriceClientRUB,
            'recommend_price' => (float) $price->RRP,
        ];
    }

    public function getDetails(string $id)
    {
        $response = $this->soapClient->getItems((object) ['item_id' => [$id]]);

        if (!$response->getItemsResult) {
            return [];
        }

        $details = end($response->getItemsResult->item);

        return [
            'id'               => (int)    $this->id,
            'category_id'      => (int)    $this->categoryId,
            'supplier'         => (string) $this->name,
            'attribute_group'  => (string) $this->attributeGroupId,
            'supplier_code'    => (string) $details->No,               // код товара
            'reference'        => (string) $details->Vendor_part,      // партномер
            'name'             => (string) $details->Name,             // наименование товара
            'brand'            => (string) $details->Brand,            // бренд
            'limit_type'       => (string) $details->Sales_Limit_Type, // тип мин. кол-ва ("Кратно", "Не Меньше", "Только Упаковками")
            'minimal_quantity' => (int)    $details->Min_Packaged,     // минимальное количество
            'weight'           => (float)  $details->Weight,           // вес (Нетто, в кг)
            'volume'           => (float)  $details->Volume,           // объем (В м3)
            'length'           => (float)  $details->Length * 1000,    // длина (метры)
            'width'            => (float)  $details->Width * 1000,     // ширина (метры)
            'height'           => (float)  $details->Height * 1000,    // высота (метры)
        ];
    }

    public function getBarcode(string $id)
    {
        $response = $this->soapClient->getItemsBarcodes((object) ['item_id' => [$id]]);

        if (!$response->getItemsBarcodesResult) {
            return [];
        }

        $barcode = end($response->getItemsBarcodesResult->item);

        return [
            'barcode' => (int) $barcode->Barcode,
        ];
    }

    public function getImages($id)
    {
        $response = $this->soapClient->getItemsImages((object) ['item_id' => [$id]]);

        if (!$response->getItemsImagesResult) {
            return [];
        }

        $images = [];

        foreach ($response->getItemsImagesResult->item as $key => $image) {
            $imageName = trim((string) $image->FileName);

            if (preg_match('/_b./', $imageName)) {
                $images[] = 'http://img.merlion.ru/items/' . $imageName;
            }
        }

        return $images;
    }

    public function getProduct(string $id)
    {
        $price    = $this->getPrice($id);
        $details  = $this->getDetails($id);
        $features = $this->getFeatures($id);
        $barcode  = $this->getBarcode($id);
        $images   = $this->getImages($id);

        $details['color'] = $details['description'] = '';

        foreach ($features as $feature) {
            switch ($feature['name']) {
                case 'Цвет':
                    $details['color'] = $feature['value'];
                    break;
                case 'Описание':
                    $details['description'] = $feature['value'];
                    break;
            }
        }

        $product = array_merge($details, $barcode, ['stock' => $price['stock']]);

        $product['price']    = $price;
        $product['images']   = $images;
        $product['features'] = $features;

        return $product;
    }
}
