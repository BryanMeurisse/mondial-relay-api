<?php

namespace Bmwsly\MondialRelayApi\Exceptions;

class ValidationException extends MondialRelayException
{
    public function __construct(string $message = 'Erreur de validation des données', int $code = 98, ?\Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
}
