<?php

namespace EO\ApiLog\Classes;

class ProductInfo
{
    private $details;
    private $features;
    private $images;
    private $price;

    public function getDetails()
    {
        return $this->details;
    }

    public function setDetails($array)
    {
        return $this->details = $array;
    }

    public function getFeatures()
    {
        return $this->features;
    }

    public function setFeatures($array)
    {
        return $this->features = $array;
    }

    public function getImages()
    {
        return $this->images;
    }

    public function setImages($array)
    {
        return $this->images = $array;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($array)
    {
        return $this->price = $array;
    }
}
