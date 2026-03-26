<?php

namespace App\Exception;

class ReservationNotAvailableException extends \RuntimeException
{
    public function __construct(string $message = 'Selected time slot is not available')
    {
        parent::__construct($message);
    }
}
