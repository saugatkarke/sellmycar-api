<?php

namespace App\Exceptions;

use Exception;

class ProductNotFoundException extends Exception
{
    public function __construct(string $message = 'Product not found/exists')
    {
        parent::__construct($message);
    }
}
