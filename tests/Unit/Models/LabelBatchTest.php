<?php

namespace Bmwsly\MondialRelayApi\Tests\Unit\Models;

use Bmwsly\MondialRelayApi\Models\LabelBatch;
use PHPUnit\Framework\TestCase;

class LabelBatchTest extends TestCase
{
    public function test_can_create_label_batch_from_api_response()
    {
        $expeditionNumbers = ['12345678901234', '56789012345678'];
        $baseUrl = 'https://api.mondialrelay.com';
        $apiResponse = (object) [
            'URL_PDF_A4' => '/batch/labels-a4.pdf',
            'URL_PDF_A5' => '/batch/labels-a5.pdf',
            'URL_PDF_10x15' => '/batch/labels-10x15.pdf',
        ];

        $labelBatch = LabelBatch::fromApiResponse($expeditionNumbers, $apiResponse, $baseUrl);

        $this->assertEquals($expeditionNumbers, $labelBatch->expeditionNumbers);
        $this->assertEquals($baseUrl.'/batch/labels-a4.pdf', $labelBatch->pdfUrlA4);
        $this->assertEquals($baseUrl.'/batch/labels-a5.pdf', $labelBatch->pdfUrlA5);
        $this->assertEquals($baseUrl.'/batch/labels-10x15.pdf', $labelBatch->pdfUrl10x15);
    }

    public function test_can_convert_to_array()
    {
        $expeditionNumbers = ['12345678901234', '56789012345678'];
        $labelBatch = new LabelBatch(
            expeditionNumbers: $expeditionNumbers,
            pdfUrlA4: 'https://example.com/batch-a4.pdf',
            pdfUrlA5: 'https://example.com/batch-a5.pdf',
            pdfUrl10x15: 'https://example.com/batch-10x15.pdf'
        );

        $array = $labelBatch->toArray();

        $this->assertIsArray($array);
        $this->assertEquals($expeditionNumbers, $array['expedition_numbers']);
        $this->assertEquals('https://example.com/batch-a4.pdf', $array['pdf_url_a4']);
        $this->assertEquals('https://example.com/batch-a5.pdf', $array['pdf_url_a5']);
        $this->assertEquals('https://example.com/batch-10x15.pdf', $array['pdf_url_10x15']);
    }

    public function test_can_get_pdf_url_by_format()
    {
        $labelBatch = new LabelBatch(
            expeditionNumbers: ['12345678901234'],
            pdfUrlA4: 'https://example.com/batch-a4.pdf',
            pdfUrlA5: 'https://example.com/batch-a5.pdf',
            pdfUrl10x15: 'https://example.com/batch-10x15.pdf'
        );

        $this->assertEquals('https://example.com/batch-a4.pdf', $labelBatch->getPdfUrlByFormat('A4'));
        $this->assertEquals('https://example.com/batch-a5.pdf', $labelBatch->getPdfUrlByFormat('A5'));
        $this->assertEquals('https://example.com/batch-10x15.pdf', $labelBatch->getPdfUrlByFormat('10x15'));

        // Test case insensitive
        $this->assertEquals('https://example.com/batch-a4.pdf', $labelBatch->getPdfUrlByFormat('a4'));
        $this->assertEquals('https://example.com/batch-10x15.pdf', $labelBatch->getPdfUrlByFormat('10X15'));
    }

    public function test_get_pdf_url_by_format_throws_exception_for_invalid_format()
    {
        $labelBatch = new LabelBatch(
            expeditionNumbers: ['12345678901234'],
            pdfUrlA4: 'https://example.com/batch-a4.pdf',
            pdfUrlA5: 'https://example.com/batch-a5.pdf',
            pdfUrl10x15: 'https://example.com/batch-10x15.pdf'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported format: INVALID. Supported formats: A4, A5, 10x15');

        $labelBatch->getPdfUrlByFormat('INVALID');
    }

    public function test_get_expedition_count()
    {
        $expeditionNumbers = ['12345678901234', '56789012345678', '90123456789012'];
        $labelBatch = new LabelBatch(
            expeditionNumbers: $expeditionNumbers,
            pdfUrlA4: 'https://example.com/batch-a4.pdf',
            pdfUrlA5: 'https://example.com/batch-a5.pdf',
            pdfUrl10x15: 'https://example.com/batch-10x15.pdf'
        );

        $this->assertEquals(3, $labelBatch->getExpeditionCount());
    }

    public function test_contains_expedition()
    {
        $expeditionNumbers = ['12345678901234', '56789012345678'];
        $labelBatch = new LabelBatch(
            expeditionNumbers: $expeditionNumbers,
            pdfUrlA4: 'https://example.com/batch-a4.pdf',
            pdfUrlA5: 'https://example.com/batch-a5.pdf',
            pdfUrl10x15: 'https://example.com/batch-10x15.pdf'
        );

        $this->assertTrue($labelBatch->containsExpedition('12345678901234'));
        $this->assertTrue($labelBatch->containsExpedition('56789012345678'));
        $this->assertFalse($labelBatch->containsExpedition('99999999999999'));
    }

    public function test_get_available_formats()
    {
        $labelBatch = new LabelBatch(
            expeditionNumbers: ['12345678901234'],
            pdfUrlA4: 'https://example.com/batch-a4.pdf',
            pdfUrlA5: 'https://example.com/batch-a5.pdf',
            pdfUrl10x15: 'https://example.com/batch-10x15.pdf'
        );

        $formats = $labelBatch->getAvailableFormats();

        $this->assertEquals(['A4', 'A5', '10x15'], $formats);
    }
}
