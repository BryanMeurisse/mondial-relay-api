<?php

namespace Bmwsly\MondialRelayApi\Tests\Unit\Models;

use Bmwsly\MondialRelayApi\Models\ExpeditionWithLabel;
use Bmwsly\MondialRelayApi\Models\Label;
use PHPUnit\Framework\TestCase;

class ExpeditionWithLabelTest extends TestCase
{
    public function test_can_create_expedition_with_label_from_api_response()
    {
        $baseUrl = 'https://api.mondialrelay.com';
        $apiResponse = (object) [
            'ExpeditionNum' => '12345678901234',
            'URL_Etiquette' => '/etiquette/test123',
        ];

        $expeditionWithLabel = ExpeditionWithLabel::fromApiResponse($apiResponse, $baseUrl);

        $this->assertEquals('12345678901234', $expeditionWithLabel->expeditionNumber);
        $this->assertInstanceOf(Label::class, $expeditionWithLabel->label);
        $this->assertEquals('12345678901234', $expeditionWithLabel->label->expeditionNumber);
    }

    public function test_can_convert_to_array()
    {
        $label = new Label(
            expeditionNumber: '12345678901234',
            labelUrlA4: 'https://example.com/label-a4.pdf',
            labelUrlA5: 'https://example.com/label-a5.pdf',
            labelUrl10x15: 'https://example.com/label-10x15.pdf'
        );

        $expeditionWithLabel = new ExpeditionWithLabel(
            expeditionNumber: '12345678901234',
            label: $label
        );

        $array = $expeditionWithLabel->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('12345678901234', $array['expedition_number']);
        $this->assertIsArray($array['label']);
        $this->assertEquals('12345678901234', $array['label']['expedition_number']);
    }

    public function test_get_tracking_url()
    {
        $label = new Label(
            expeditionNumber: '12345678901234',
            labelUrlA4: 'https://example.com/label-a4.pdf',
            labelUrlA5: 'https://example.com/label-a5.pdf',
            labelUrl10x15: 'https://example.com/label-10x15.pdf'
        );

        $expeditionWithLabel = new ExpeditionWithLabel(
            expeditionNumber: '12345678901234',
            label: $label
        );

        $trackingUrl = $expeditionWithLabel->getTrackingUrl();

        $this->assertEquals('https://www.mondialrelay.fr/suivi-de-colis/?numeroExpedition=12345678901234', $trackingUrl);
    }

    public function test_get_label_url()
    {
        $label = new Label(
            expeditionNumber: '12345678901234',
            labelUrlA4: 'https://example.com/label-a4.pdf',
            labelUrlA5: 'https://example.com/label-a5.pdf',
            labelUrl10x15: 'https://example.com/label-10x15.pdf'
        );

        $expeditionWithLabel = new ExpeditionWithLabel(
            expeditionNumber: '12345678901234',
            label: $label
        );

        // Test default format (A4)
        $this->assertEquals('https://example.com/label-a4.pdf', $expeditionWithLabel->getLabelUrl());

        // Test specific formats
        $this->assertEquals('https://example.com/label-a4.pdf', $expeditionWithLabel->getLabelUrl('A4'));
        $this->assertEquals('https://example.com/label-a5.pdf', $expeditionWithLabel->getLabelUrl('A5'));
        $this->assertEquals('https://example.com/label-10x15.pdf', $expeditionWithLabel->getLabelUrl('10x15'));
    }

    public function test_download_label_success()
    {
        // Create a temporary file to simulate the PDF
        $expectedContent = '%PDF-1.4 fake pdf content';
        $tempFile = tempnam(sys_get_temp_dir(), 'test_pdf');
        file_put_contents($tempFile, $expectedContent);

        $label = new Label(
            expeditionNumber: '12345678901234',
            labelUrlA4: "file://{$tempFile}", // Use file:// protocol for local file
            labelUrlA5: 'https://example.com/label-a5.pdf',
            labelUrl10x15: 'https://example.com/label-10x15.pdf'
        );

        $expeditionWithLabel = new ExpeditionWithLabel(
            expeditionNumber: '12345678901234',
            label: $label
        );

        $content = $expeditionWithLabel->downloadLabel('A4');

        $this->assertEquals($expectedContent, $content);

        // Clean up
        unlink($tempFile);
    }

    public function test_download_label_failure()
    {
        $label = new Label(
            expeditionNumber: '12345678901234',
            labelUrlA4: 'https://invalid-url-that-does-not-exist.com/label.pdf',
            labelUrlA5: 'https://example.com/label-a5.pdf',
            labelUrl10x15: 'https://example.com/label-10x15.pdf'
        );

        $expeditionWithLabel = new ExpeditionWithLabel(
            expeditionNumber: '12345678901234',
            label: $label
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to download label PDF from:');

        $expeditionWithLabel->downloadLabel('A4');
    }
}
