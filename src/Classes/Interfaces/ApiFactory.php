<?php

namespace EO\ApiLog\Classes\Interfaces;

use EO\ApiLog\Classes\Interfaces\Api;

interface ApiFactory
{
    public function createApi(string $supplier): Api;
}
