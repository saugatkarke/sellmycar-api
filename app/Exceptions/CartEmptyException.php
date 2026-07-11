<?php

namespace App\Exceptions;

use Exception;

class CartEmptyException extends Exception
{
    public function __construct(
        string $message = 'Cart is Empty. Please add item to the cart first!'
    ) {
        parent::__construct($message);
    }
}
