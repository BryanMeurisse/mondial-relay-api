<?php

namespace Bmwsly\MondialRelayApi\Exceptions;

class ServiceException extends MondialRelayException
{
    public function __construct(string $message = 'Erreur du service Mondial Relay', int $code = 99, ?\Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous, $context);
    }
}
