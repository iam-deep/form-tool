<?php

namespace Deep\FormTool\Tests\Unit\Support;

use Deep\FormTool\Support\ImageCache;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Tests\TestCase;

class ImageCacheFilesystemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('form-tool-images');
        config([
            'form-tool.filesystem.disk' => 'form-tool-images',
            'form-tool.filesystem.visibility' => 'private',
            'form-tool.imageCachePath' => 'cache',
            'form-tool.imageCacheWidth' => 20,
            'form-tool.imageCacheHeight' => 20,
        ]);
    }

    public function test_resize_reads_and_writes_derivatives_on_the_selected_disk(): void
    {
        $source = UploadedFile::fake()->image('student.png', 80, 40);
        Storage::disk('form-tool-images')->put(
            'storage/students/student.png',
            file_get_contents($source->getRealPath()),
            'private'
        );
        $temporaryFilesBefore = glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'form-tool-image-*') ?: [];

        $cached = ImageCache::resize(
            'storage/students/student.png',
            20,
            20,
            'form-tool-images',
            'private'
        );

        $this->assertSame('cache/storage/students/student-20x20.png', $cached);
        Storage::disk('form-tool-images')->assertExists($cached);

        $image = Image::make(Storage::disk('form-tool-images')->get($cached));
        $this->assertSame(20, $image->width());
        $this->assertSame(10, $image->height());
        $this->assertSame($cached, ImageCache::resize(
            'storage/students/student.png',
            20,
            20,
            'form-tool-images',
            'private'
        ));
        $this->assertSame(
            $temporaryFilesBefore,
            glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'form-tool-image-*') ?: []
        );
    }

    public function test_non_resizable_files_return_the_original_disk_key(): void
    {
        Storage::disk('form-tool-images')->put('storage/documents/report.pdf', 'pdf');

        $this->assertSame(
            'storage/documents/report.pdf',
            ImageCache::resize(
                'storage/documents/report.pdf',
                20,
                20,
                'form-tool-images',
                'private'
            )
        );
    }

    public function test_clear_cache_deletes_remote_derivatives_only(): void
    {
        Storage::disk('form-tool-images')->put('cache/storage/a.png', 'cache');
        Storage::disk('form-tool-images')->put('storage/a.png', 'source');

        ImageCache::clearCache('form-tool-images');

        Storage::disk('form-tool-images')->assertMissing('cache/storage/a.png');
        Storage::disk('form-tool-images')->assertExists('storage/a.png');
    }
}
