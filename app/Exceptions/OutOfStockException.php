<?php

namespace App\Exceptions;

use Exception;

class OutOfStockException extends Exception
{
    public function __construct(
        string $message = 'The requested quantity exceeds the available stock.'
    ) {
        parent::__construct($message);
    }
}
