<?php

namespace Deep\FormTool\Tests\Unit\Views;

use Deep\FormTool\Tests\TestCase;

class FileInputCropMetadataTest extends TestCase
{
    public function test_crop_enabled_single_image_renders_crop_metadata(): void
    {
        $html = $this->renderFileInput($this->singleImageInput([
            'hasCrop' => true,
            'cropWidth' => 640,
            'cropHeight' => 480,
        ]));

        $this->assertStringContainsString('data-form-tool-crop="1"', $html);
        $this->assertStringContainsString('data-crop-width="640"', $html);
        $this->assertStringContainsString('data-crop-height="480"', $html);
        $this->assertStringContainsString('change formtool:cropped', $html);
    }

    public function test_image_without_crop_omits_crop_metadata(): void
    {
        $html = $this->renderFileInput($this->singleImageInput());

        $this->assertStringNotContainsString('data-form-tool-crop=', $html);
        $this->assertStringNotContainsString('data-crop-width=', $html);
        $this->assertStringNotContainsString('data-crop-height=', $html);
    }

    public function test_crop_enabled_multiple_image_renders_crop_metadata(): void
    {
        $html = $this->renderFileInput($this->multipleImageInput([
            'hasCrop' => true,
            'cropWidth' => 300,
            'cropHeight' => 400,
        ]));

        $this->assertStringContainsString('data-form-tool-crop="1"', $html);
        $this->assertStringContainsString('data-crop-width="300"', $html);
        $this->assertStringContainsString('data-crop-height="400"', $html);
    }

    private function renderFileInput(object $input): string
    {
        ob_start();
        include dirname(__DIR__, 3).'/src/views/form/input_types/file.php';

        return ob_get_clean();
    }

    private function singleImageInput(array $overrides = []): object
    {
        return (object) array_merge([
            'type' => 'single',
            'column' => 'image',
            'rawValue' => '',
            'value' => '',
            'classes' => '',
            'raw' => '',
            'maxSize' => 5120,
            'isImageField' => true,
            'accept' => 'image/*',
            'formats' => 'png, jpg, svg & webp',
            'isImage' => false,
            'imageCache' => '/placeholder.png',
            'noImage' => '/placeholder.png',
            'icon' => 'fa fa-file',
            'hasCrop' => false,
            'cropWidth' => null,
            'cropHeight' => null,
        ], $overrides);
    }

    private function multipleImageInput(array $overrides = []): object
    {
        return (object) array_merge([
            'type' => 'multiple',
            'key' => 'documents',
            'index' => 0,
            'column' => 'image',
            'rawValue' => '',
            'value' => '',
            'oldValue' => null,
            'id' => 'documents-image-0',
            'name' => 'documents[0][image]',
            'classes' => '',
            'raw' => '',
            'isRequired' => false,
            'groupId' => 'documents-group-image-0',
            'maxSize' => 5120,
            'isImageField' => true,
            'accept' => 'image/*',
            'formats' => 'png, jpg, svg & webp',
            'isImage' => false,
            'imageCache' => null,
            'noImage' => '/placeholder.png',
            'icon' => 'fa fa-file',
            'hasCrop' => false,
            'cropWidth' => null,
            'cropHeight' => null,
        ], $overrides);
    }
}
