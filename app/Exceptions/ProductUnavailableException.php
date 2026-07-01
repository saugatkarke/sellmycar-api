<?php

namespace App\Exceptions;

use Exception;

class ProductUnavailableException extends Exception
{
    public function __construct(
        string $message = 'Product currently not available!'
    ) {
        parent::__construct($message);
    }
}
