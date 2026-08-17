<?php

namespace Deep\FormTool\Tests\Unit\Core\InputTypes;

use Deep\FormTool\Core\Doc;
use Deep\FormTool\Core\InputTypes\FileType;
use Deep\FormTool\Core\InputTypes\ImageType;
use Deep\FormTool\Tests\TestCase;
use ReflectionProperty;

class FileTypeCropperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetDocAssets();
    }

    protected function tearDown(): void
    {
        $this->resetDocAssets();

        parent::tearDown();
    }

    public function test_crop_enabled_image_normalizes_dimensions_and_registers_dependencies_once(): void
    {
        $field = (new InspectableImageType())->crop(640, 480);
        $field->crop(640, 480);

        $data = $field->exposeFileInputCropData();

        $this->assertTrue($data->hasCrop);
        $this->assertSame(640, $data->cropWidth);
        $this->assertSame(480, $data->cropHeight);
        $this->assertSame(1, substr_count(Doc::getCssLinks(), 'cropper.min.css'));
        $this->assertSame(1, substr_count(Doc::getJsLinks(), 'cropper.min.js'));
    }

    public function test_crop_uses_width_as_default_browser_height(): void
    {
        $data = (new InspectableImageType())->crop(320)->exposeFileInputCropData();

        $this->assertSame(320, $data->cropWidth);
        $this->assertSame(320, $data->cropHeight);
    }

    public function test_image_without_crop_has_no_browser_crop_configuration(): void
    {
        $data = (new InspectableImageType())->exposeFileInputCropData();

        $this->assertFalse($data->hasCrop);
        $this->assertNull($data->cropWidth);
        $this->assertNull($data->cropHeight);
        $this->assertSame('', Doc::getCssLinks());
        $this->assertSame('', Doc::getJsLinks());
    }

    public function test_non_image_file_does_not_enable_browser_cropper(): void
    {
        $data = (new InspectableFileType())->crop(320, 240)->exposeFileInputCropData();

        $this->assertFalse($data->hasCrop);
        $this->assertSame('', Doc::getCssLinks());
        $this->assertSame('', Doc::getJsLinks());
    }

    private function resetDocAssets(): void
    {
        foreach (['cssLink', 'jsLink', 'css', 'js', 'jsGroup'] as $propertyName) {
            $property = new ReflectionProperty(Doc::class, $propertyName);
            $property->setValue(null, []);
        }
    }
}

final class InspectableImageType extends ImageType
{
    public function exposeFileInputCropData(): object
    {
        return (object) $this->fileInputCropData();
    }
}

final class InspectableFileType extends FileType
{
    public function exposeFileInputCropData(): object
    {
        return (object) $this->fileInputCropData();
    }
}
