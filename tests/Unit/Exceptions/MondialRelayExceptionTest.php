<?php

namespace Bmwsly\MondialRelayApi\Tests\Unit\Exceptions;

use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;
use PHPUnit\Framework\TestCase;

class MondialRelayExceptionTest extends TestCase
{
    public function test_can_create_exception_with_context()
    {
        $context = ['param1' => 'value1', 'param2' => 'value2'];
        $exception = new MondialRelayException('Test message', 98, null, $context);

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(98, $exception->getCode());
        $this->assertEquals($context, $exception->getContext());
    }

    public function test_can_add_context()
    {
        $exception = new MondialRelayException('Test message');
        $exception->addContext('key', 'value');

        $this->assertEquals(['key' => 'value'], $exception->getContext());
    }

    public function test_can_set_context()
    {
        $exception = new MondialRelayException('Test message');
        $context = ['key1' => 'value1', 'key2' => 'value2'];
        $exception->setContext($context);

        $this->assertEquals($context, $exception->getContext());
    }

    public function test_get_user_message_for_known_codes()
    {
        $exception = new MondialRelayException('Technical message', 1);
        $this->assertEquals('Enseigne invalide. Vérifiez vos identifiants Mondial Relay.', $exception->getUserMessage());

        $exception = new MondialRelayException('Technical message', 8);
        $this->assertEquals('Mot de passe ou clé de sécurité invalide.', $exception->getUserMessage());

        $exception = new MondialRelayException('Technical message', 36);
        $this->assertEquals('Code postal invalide.', $exception->getUserMessage());
    }

    public function test_get_user_message_for_unknown_code()
    {
        $exception = new MondialRelayException('Technical message', 999);
        $this->assertEquals('Technical message', $exception->getUserMessage());
    }

    public function test_is_recoverable()
    {
        $recoverableException = new MondialRelayException('Test', 36); // Postal code error
        $this->assertTrue($recoverableException->isRecoverable());

        $nonRecoverableException = new MondialRelayException('Test', 1); // Brand error
        $this->assertFalse($nonRecoverableException->isRecoverable());
    }

    public function test_is_authentication_error()
    {
        $authException = new MondialRelayException('Test', 8);
        $this->assertTrue($authException->isAuthenticationError());

        $validationException = new MondialRelayException('Test', 36);
        $this->assertFalse($validationException->isAuthenticationError());
    }

    public function test_is_validation_error()
    {
        $validationException = new MondialRelayException('Test', 36);
        $this->assertTrue($validationException->isValidationError());

        $authException = new MondialRelayException('Test', 8);
        $this->assertFalse($authException->isValidationError());
    }

    public function test_get_category()
    {
        $authException = new MondialRelayException('Test', 8);
        $this->assertEquals('authentication', $authException->getCategory());

        $validationException = new MondialRelayException('Test', 36);
        $this->assertEquals('validation', $validationException->getCategory());

        $notFoundException = new MondialRelayException('Test', 94);
        $this->assertEquals('not_found', $notFoundException->getCategory());

        $serviceException = new MondialRelayException('Test', 99);
        $this->assertEquals('service', $serviceException->getCategory());

        $unknownException = new MondialRelayException('Test', 999);
        $this->assertEquals('unknown', $unknownException->getCategory());
    }

    public function test_to_array()
    {
        $context = ['param' => 'value'];
        $exception = new MondialRelayException('Test message', 36, null, $context);
        $array = $exception->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('Test message', $array['message']);
        $this->assertEquals('Code postal invalide.', $array['user_message']);
        $this->assertEquals(36, $array['code']);
        $this->assertEquals('validation', $array['category']);
        $this->assertTrue($array['is_recoverable']);
        $this->assertEquals($context, $array['context']);
        $this->assertArrayHasKey('file', $array);
        $this->assertArrayHasKey('line', $array);
        $this->assertArrayHasKey('trace', $array);
    }
}
