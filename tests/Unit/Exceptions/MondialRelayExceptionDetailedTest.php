<?php

namespace Bmwsly\MondialRelayApi\Tests\Unit\Exceptions;

use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;
use PHPUnit\Framework\TestCase;

class MondialRelayExceptionDetailedTest extends TestCase
{
    public function test_from_api_response_with_detailed_context()
    {
        $response = [
            'STAT' => '9',
        ];
        
        $context = [
            'method' => 'searchRelayPoints',
            'postal_code' => '99999',
            'country' => 'FR',
            'enseigne' => 'CC23KDJZ',
        ];

        $exception = MondialRelayException::fromApiResponse($response, $context);

        $this->assertEquals(9, $exception->getCode());
        $this->assertStringContainsString('Ville inconnue ou non unique', $exception->getMessage());
        $this->assertStringContainsString('Méthode: searchRelayPoints', $exception->getMessage());
        $this->assertStringContainsString('Code postal: 99999', $exception->getMessage());
        $this->assertStringContainsString('Pays: FR', $exception->getMessage());
        $this->assertStringContainsString('Enseigne: CC23KDJZ', $exception->getMessage());
        $this->assertStringContainsString('[Code erreur API: 9]', $exception->getMessage());
    }

    public function test_from_api_response_with_unknown_error_code()
    {
        $response = [
            'STAT' => '999',
        ];
        
        $context = [
            'method' => 'searchRelayPoints',
            'postal_code' => '12345',
        ];

        $exception = MondialRelayException::fromApiResponse($response, $context);

        $this->assertEquals(999, $exception->getCode());
        $this->assertStringContainsString('Erreur inconnue', $exception->getMessage());
        $this->assertStringContainsString('Méthode: searchRelayPoints', $exception->getMessage());
        $this->assertStringContainsString('Code postal: 12345', $exception->getMessage());
        $this->assertStringContainsString('[Code erreur API: 999]', $exception->getMessage());
    }

    public function test_get_debug_info()
    {
        $response = [
            'STAT' => '1',
        ];
        
        $context = [
            'method' => 'searchRelayPoints',
            'enseigne' => 'INVALID',
        ];

        $exception = MondialRelayException::fromApiResponse($response, $context);
        $debugInfo = $exception->getDebugInfo();

        $this->assertArrayHasKey('message', $debugInfo);
        $this->assertArrayHasKey('code', $debugInfo);
        $this->assertArrayHasKey('context', $debugInfo);
        $this->assertArrayHasKey('file', $debugInfo);
        $this->assertArrayHasKey('line', $debugInfo);
        $this->assertArrayHasKey('trace', $debugInfo);
        
        $this->assertEquals(1, $debugInfo['code']);
        $this->assertArrayHasKey('api_response', $debugInfo['context']);
        $this->assertArrayHasKey('api_error_code', $debugInfo['context']);
        $this->assertArrayHasKey('base_message', $debugInfo['context']);
    }

    public function test_is_api_error()
    {
        $response = [
            'STAT' => '1',
        ];

        $exception = MondialRelayException::fromApiResponse($response);
        
        $this->assertTrue($exception->isApiError());
    }

    public function test_context_preservation()
    {
        $response = [
            'STAT' => '10',
        ];

        $context = [
            'method' => 'createExpedition',
            'params' => ['test' => 'value'],
            'enseigne' => 'CC23KDJZ',
        ];

        $exception = MondialRelayException::fromApiResponse($response, $context);
        $exceptionContext = $exception->getContext();

        $this->assertArrayHasKey('method', $exceptionContext);
        $this->assertArrayHasKey('params', $exceptionContext);
        $this->assertArrayHasKey('enseigne', $exceptionContext);
        $this->assertArrayHasKey('api_response', $exceptionContext);
        $this->assertArrayHasKey('api_error_code', $exceptionContext);
        $this->assertArrayHasKey('base_message', $exceptionContext);
        $this->assertArrayHasKey('timestamp', $exceptionContext);

        $this->assertEquals('createExpedition', $exceptionContext['method']);
        $this->assertEquals(['test' => 'value'], $exceptionContext['params']);
        $this->assertEquals('CC23KDJZ', $exceptionContext['enseigne']);
        $this->assertEquals(10, $exceptionContext['api_error_code']);
    }

    public function test_error_code_92_handling()
    {
        $response = [
            'STAT' => '92',
        ];

        $context = [
            'method' => 'createExpeditionWithLabel',
            'enseigne' => 'CC23KDJZ',
            'delivery_mode' => '24R',
            'weight' => '1000',
        ];

        $exception = MondialRelayException::fromApiResponse($response, $context);

        $this->assertEquals(92, $exception->getCode());
        $this->assertStringContainsString('Erreur lors de la génération de l\'étiquette ou du traitement de l\'expédition', $exception->getMessage());
        $this->assertStringContainsString('Méthode: createExpeditionWithLabel', $exception->getMessage());
        $this->assertStringContainsString('Enseigne: CC23KDJZ', $exception->getMessage());
        $this->assertStringContainsString('[Code erreur API: 92]', $exception->getMessage());
        $this->assertTrue($exception->isApiError());

        $context = $exception->getContext();
        $this->assertEquals('Erreur lors de la génération de l\'étiquette ou du traitement de l\'expédition', $context['base_message']);
        $this->assertEquals(92, $context['api_error_code']);
    }
}
