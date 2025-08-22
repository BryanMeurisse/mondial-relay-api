<?php

namespace Bmwsly\MondialRelayApi\Exceptions;

class AuthenticationException extends MondialRelayException
{
    public function __construct(string $message = 'Erreur d\'authentification Mondial Relay', int $code = 8, ?\Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
}
