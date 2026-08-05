<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedOrderPaymentException extends Exception
{
    public function __construct(
        string $message = 'You are not authorised to pay for this order.',
        public int $statusCode = 403
    ) {
        parent::__construct($message);
    }
}
