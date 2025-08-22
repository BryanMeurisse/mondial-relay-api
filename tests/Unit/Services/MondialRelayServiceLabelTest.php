<?php

namespace Bmwsly\MondialRelayApi\Tests\Unit\Services;

use Bmwsly\MondialRelayApi\Exceptions\MondialRelayException;
use Bmwsly\MondialRelayApi\Models\ExpeditionWithLabel;
use Bmwsly\MondialRelayApi\Models\Label;
use Bmwsly\MondialRelayApi\Models\LabelBatch;
use Bmwsly\MondialRelayApi\MondialRelayClient;
use Bmwsly\MondialRelayApi\Services\MondialRelayService;
use Mockery;
use PHPUnit\Framework\TestCase;

class MondialRelayServiceLabelTest extends TestCase
{
    private MondialRelayClient $mockClient;
    private MondialRelayService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock(MondialRelayClient::class);
        $this->service = new MondialRelayService($this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_expedition_with_label_success()
    {
        $sender = [
            'name' => 'Test Sender',
            'address' => '123 Test Street',
            'city' => 'Paris',
            'postal_code' => '75001',
            'country' => 'FR',
            'phone' => '0123456789',
        ];

        $recipient = [
            'name' => 'Test Recipient',
            'address' => '456 Test Avenue',
            'city' => 'Lyon',
            'postal_code' => '69001',
            'country' => 'FR',
            'phone' => '0987654321',
        ];

        $label = new Label(
            expeditionNumber: '12345678901234',
            labelUrlA4: 'https://example.com/label-a4.pdf',
            labelUrlA5: 'https://example.com/label-a5.pdf',
            labelUrl10x15: 'https://example.com/label-10x15.pdf'
        );

        $expectedExpedition = new ExpeditionWithLabel(
            expeditionNumber: '12345678901234',
            label: $label
        );

        $this->mockClient
            ->shouldReceive('createExpeditionWithLabel')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn($expectedExpedition);

        $result = $this->service->createExpeditionWithLabel(
            $sender,
            $recipient,
            '123456',
            1000,
            '24R',
            'ORDER-001',
            'Test articles'
        );

        $this->assertInstanceOf(ExpeditionWithLabel::class, $result);
        $this->assertEquals('12345678901234', $result->expeditionNumber);
    }

    public function test_create_expedition_with_label_invalid_relay_number()
    {
        $sender = ['name' => 'Test'];
        $recipient = ['name' => 'Test'];

        $this->expectException(MondialRelayException::class);
        $this->expectExceptionMessage('Numéro de point relais invalide');

        $this->service->createExpeditionWithLabel(
            $sender,
            $recipient,
            '12345', // Invalid relay number (should be 6 digits)
            1000
        );
    }

    public function test_create_home_delivery_expedition_with_label_success()
    {
        $sender = [
            'name' => 'Test Sender',
            'address' => '123 Test Street',
            'city' => 'Paris',
            'postal_code' => '75001',
            'country' => 'FR',
            'phone' => '0123456789',
        ];

        $recipient = [
            'name' => 'Test Recipient',
            'address' => '456 Test Avenue',
            'city' => 'Lyon',
            'postal_code' => '69001',
            'country' => 'FR',
            'phone' => '0987654321',
        ];

        $label = new Label(
            expeditionNumber: '12345678901234',
            labelUrlA4: 'https://example.com/label-a4.pdf',
            labelUrlA5: 'https://example.com/label-a5.pdf',
            labelUrl10x15: 'https://example.com/label-10x15.pdf'
        );

        $expectedExpedition = new ExpeditionWithLabel(
            expeditionNumber: '12345678901234',
            label: $label
        );

        $this->mockClient
            ->shouldReceive('createExpeditionWithLabel')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn($expectedExpedition);

        $result = $this->service->createHomeDeliveryExpeditionWithLabel(
            $sender,
            $recipient,
            1000,
            '24L',
            'ORDER-001',
            'Test articles'
        );

        $this->assertInstanceOf(ExpeditionWithLabel::class, $result);
        $this->assertEquals('12345678901234', $result->expeditionNumber);
    }

    public function test_get_labels_for_expeditions_success()
    {
        $expeditionNumbers = ['12345678901234', '56789012345678'];

        $expectedLabelBatch = new LabelBatch(
            expeditionNumbers: $expeditionNumbers,
            pdfUrlA4: 'https://example.com/batch-a4.pdf',
            pdfUrlA5: 'https://example.com/batch-a5.pdf',
            pdfUrl10x15: 'https://example.com/batch-10x15.pdf'
        );

        $this->mockClient
            ->shouldReceive('getLabels')
            ->once()
            ->with($expeditionNumbers)
            ->andReturn($expectedLabelBatch);

        $result = $this->service->getLabelsForExpeditions($expeditionNumbers);

        $this->assertInstanceOf(LabelBatch::class, $result);
        $this->assertEquals($expeditionNumbers, $result->expeditionNumbers);
    }

    public function test_get_labels_for_expeditions_empty_array()
    {
        $this->expectException(MondialRelayException::class);
        $this->expectExceptionMessage('Au moins un numéro d\'expédition est requis');

        $this->service->getLabelsForExpeditions([]);
    }

    public function test_download_label_pdf()
    {
        $labelUrl = 'https://example.com/label.pdf';
        $expectedContent = '%PDF-1.4 fake pdf content';

        $this->mockClient
            ->shouldReceive('downloadLabel')
            ->once()
            ->with($labelUrl)
            ->andReturn($expectedContent);

        $result = $this->service->downloadLabelPdf($labelUrl);

        $this->assertEquals($expectedContent, $result);
    }

    public function test_download_expedition_label()
    {
        $label = new Label(
            expeditionNumber: '12345678901234',
            labelUrlA4: 'https://example.com/label-a4.pdf',
            labelUrlA5: 'https://example.com/label-a5.pdf',
            labelUrl10x15: 'https://example.com/label-10x15.pdf'
        );

        $expedition = new ExpeditionWithLabel(
            expeditionNumber: '12345678901234',
            label: $label
        );

        $expectedContent = '%PDF-1.4 fake pdf content';

        $this->mockClient
            ->shouldReceive('downloadLabel')
            ->once()
            ->with('https://example.com/label-a4.pdf')
            ->andReturn($expectedContent);

        $result = $this->service->downloadExpeditionLabel($expedition, 'A4');

        $this->assertEquals($expectedContent, $result);
    }

    public function test_download_batch_labels()
    {
        $labelBatch = new LabelBatch(
            expeditionNumbers: ['12345678901234'],
            pdfUrlA4: 'https://example.com/batch-a4.pdf',
            pdfUrlA5: 'https://example.com/batch-a5.pdf',
            pdfUrl10x15: 'https://example.com/batch-10x15.pdf'
        );

        $expectedContent = '%PDF-1.4 fake pdf content';

        $this->mockClient
            ->shouldReceive('downloadLabel')
            ->once()
            ->with('https://example.com/batch-a4.pdf')
            ->andReturn($expectedContent);

        $result = $this->service->downloadBatchLabels($labelBatch, 'A4');

        $this->assertEquals($expectedContent, $result);
    }
}
