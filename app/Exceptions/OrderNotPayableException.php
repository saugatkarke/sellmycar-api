<?php

namespace App\Exceptions;

use Exception;

class OrderNotPayableException extends Exception
{
    public function __construct(
        string $message = 'Order cannot be paid!',
        public int $statusCode = 422
    ) {
        parent::__construct($message);
    }
}
