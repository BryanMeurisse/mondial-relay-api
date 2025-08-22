<?php

namespace Bmwsly\MondialRelayApi\Tests\Unit\Models;

use Bmwsly\MondialRelayApi\Models\Label;
use PHPUnit\Framework\TestCase;

class LabelTest extends TestCase
{
    public function test_can_create_label_from_api_response()
    {
        $expeditionNumber = '12345678901234';
        $baseUrl = 'https://api.mondialrelay.com';
        $apiResponse = (object) [
            'URL_Etiquette' => '/etiquette/test123',
        ];

        $label = Label::fromApiResponse($expeditionNumber, $apiResponse, $baseUrl);

        $this->assertEquals($expeditionNumber, $label->expeditionNumber);
        $this->assertEquals($baseUrl.'/etiquette/test123&format=A4', $label->labelUrlA4);
        $this->assertEquals($baseUrl.'/etiquette/test123&format=A5', $label->labelUrlA5);
        $this->assertEquals($baseUrl.'/etiquette/test123&format=10x15', $label->labelUrl10x15);
    }

    public function test_can_create_label_from_urls()
    {
        $expeditionNumber = '12345678901234';
        $urlA4 = 'https://example.com/label-a4.pdf';
        $urlA5 = 'https://example.com/label-a5.pdf';
        $url10x15 = 'https://example.com/label-10x15.pdf';

        $label = Label::fromLabelUrls($expeditionNumber, $urlA4, $urlA5, $url10x15);

        $this->assertEquals($expeditionNumber, $label->expeditionNumber);
        $this->assertEquals($urlA4, $label->labelUrlA4);
        $this->assertEquals($urlA5, $label->labelUrlA5);
        $this->assertEquals($url10x15, $label->labelUrl10x15);
    }

    public function test_can_convert_to_array()
    {
        $label = new Label(
            expeditionNumber: '12345678901234',
            labelUrlA4: 'https://example.com/label-a4.pdf',
            labelUrlA5: 'https://example.com/label-a5.pdf',
            labelUrl10x15: 'https://example.com/label-10x15.pdf'
        );

        $array = $label->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('12345678901234', $array['expedition_number']);
        $this->assertEquals('https://example.com/label-a4.pdf', $array['label_url_a4']);
        $this->assertEquals('https://example.com/label-a5.pdf', $array['label_url_a5']);
        $this->assertEquals('https://example.com/label-10x15.pdf', $array['label_url_10x15']);
    }

    public function test_can_get_url_by_format()
    {
        $label = new Label(
            expeditionNumber: '12345678901234',
            labelUrlA4: 'https://example.com/label-a4.pdf',
            labelUrlA5: 'https://example.com/label-a5.pdf',
            labelUrl10x15: 'https://example.com/label-10x15.pdf'
        );

        $this->assertEquals('https://example.com/label-a4.pdf', $label->getUrlByFormat('A4'));
        $this->assertEquals('https://example.com/label-a5.pdf', $label->getUrlByFormat('A5'));
        $this->assertEquals('https://example.com/label-10x15.pdf', $label->getUrlByFormat('10x15'));

        // Test case insensitive
        $this->assertEquals('https://example.com/label-a4.pdf', $label->getUrlByFormat('a4'));
        $this->assertEquals('https://example.com/label-10x15.pdf', $label->getUrlByFormat('10X15'));
    }

    public function test_get_url_by_format_throws_exception_for_invalid_format()
    {
        $label = new Label(
            expeditionNumber: '12345678901234',
            labelUrlA4: 'https://example.com/label-a4.pdf',
            labelUrlA5: 'https://example.com/label-a5.pdf',
            labelUrl10x15: 'https://example.com/label-10x15.pdf'
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported format: INVALID. Supported formats: A4, A5, 10x15');

        $label->getUrlByFormat('INVALID');
    }

    public function test_get_available_formats()
    {
        $label = new Label(
            expeditionNumber: '12345678901234',
            labelUrlA4: 'https://example.com/label-a4.pdf',
            labelUrlA5: 'https://example.com/label-a5.pdf',
            labelUrl10x15: 'https://example.com/label-10x15.pdf'
        );

        $formats = $label->getAvailableFormats();

        $this->assertEquals(['A4', 'A5', '10x15'], $formats);
    }

    public function test_has_format()
    {
        $label = new Label(
            expeditionNumber: '12345678901234',
            labelUrlA4: 'https://example.com/label-a4.pdf',
            labelUrlA5: 'https://example.com/label-a5.pdf',
            labelUrl10x15: 'https://example.com/label-10x15.pdf'
        );

        $this->assertTrue($label->hasFormat('A4'));
        $this->assertTrue($label->hasFormat('A5'));
        $this->assertTrue($label->hasFormat('10x15'));
        $this->assertTrue($label->hasFormat('a4')); // case insensitive
        $this->assertTrue($label->hasFormat('10X15')); // case insensitive
        $this->assertFalse($label->hasFormat('INVALID'));
    }
}
