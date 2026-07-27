<?php

namespace Deep\FormTool\Tests\Unit\Support;

use Deep\FormTool\Exceptions\FormToolException;
use Deep\FormTool\Support\FileManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileManagerFilesystemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('form-tool-test');
        config([
            'filesystems.disks.form-tool-test' => [
                'driver' => 'local',
                'root' => storage_path('framework/testing/disks/form-tool-test'),
            ],
            'form-tool.filesystem.disk' => 'form-tool-test',
            'form-tool.filesystem.visibility' => 'private',
            'form-tool.uploadPath' => 'storage',
            'form-tool.uploadSubDirFormat' => '',
        ]);
    }

    public function test_upload_returns_the_existing_storage_prefixed_key(): void
    {
        $path = FileManager::uploadFile(
            UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
            'students'
        );

        $this->assertSame('storage/students/report.pdf', $path);
        Storage::disk('form-tool-test')->assertExists($path);
        $this->assertSame('private', FileManager::visibility());
    }

    public function test_remote_upload_requires_explicit_visibility_before_writing(): void
    {
        config([
            'filesystems.disks.remote-test' => ['driver' => 's3'],
            'form-tool.filesystem.disk' => 'remote-test',
            'form-tool.filesystem.visibility' => 'public',
        ]);

        $this->expectException(FormToolException::class);
        $this->expectExceptionMessage(
            'File visibility must be explicitly set for non-local disk [remote-test].'
        );

        FileManager::uploadFile(
            UploadedFile::fake()->create('report.pdf', 10, 'application/pdf'),
            'students',
        );
    }

    public function test_remote_visibility_accepts_explicit_public_and_private_values(): void
    {
        config(['filesystems.disks.remote-test' => ['driver' => 's3']]);

        $this->assertSame('public', FileManager::visibility('public', 'remote-test'));
        $this->assertSame('private', FileManager::visibility('private', 'remote-test'));
    }

    public function test_copy_delete_exists_size_stream_and_directory_deletion_use_the_selected_disk(): void
    {
        Storage::disk('form-tool-test')->put('storage/a.txt', 'alpha', 'private');

        $copy = FileManager::copyFile('storage/a.txt');

        $this->assertSame('storage/a_2.txt', $copy);
        $this->assertTrue(FileManager::exists($copy));
        $this->assertSame(5, FileManager::size($copy));

        $stream = FileManager::readStream($copy);
        $this->assertIsResource($stream);
        $this->assertSame('alpha', stream_get_contents($stream));
        fclose($stream);

        $this->assertTrue(FileManager::deleteFile($copy));
        $this->assertFalse(FileManager::exists($copy));

        Storage::disk('form-tool-test')->put('cache/one.txt', 'one');
        $this->assertTrue(FileManager::deleteDirectory('cache'));
        Storage::disk('form-tool-test')->assertMissing('cache/one.txt');
    }
}
