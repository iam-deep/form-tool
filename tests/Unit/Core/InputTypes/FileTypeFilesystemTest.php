<?php

namespace Deep\FormTool\Tests\Unit\Core\InputTypes;

use DateTimeInterface;
use Deep\FormTool\Contracts\PrivateFileUrlResolver;
use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Exceptions\FormToolException;
use Deep\FormTool\Support\FileManager;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TestPrivateFileUrlResolver implements PrivateFileUrlResolver
{
    public function resolve(string $path, string $disk, DateTimeInterface $expiresAt): string
    {
        return 'https://app.example.test/private/'.rawurlencode($disk.'|'.$path)
            .'?expires='.$expiresAt->getTimestamp();
    }
}

class FileTypeFilesystemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'filesystems.disks.form-tool-public' => [
                'driver' => 'local',
                'root' => storage_path('framework/testing/disks/form-tool-public'),
                'url' => 'https://cdn.example.test',
                'visibility' => 'public',
            ],
            'form-tool.filesystem.disk' => 'form-tool-public',
            'form-tool.filesystem.visibility' => 'public',
            'form-tool.filesystem.privateUrlTtlMinutes' => 5,
        ]);
        Storage::forgetDisk('form-tool-public');
    }

    public function test_file_fields_validate_and_expose_disk_and_visibility_overrides(): void
    {
        $input = (new BluePrint())
            ->file('document')
            ->disk('archive')
            ->visibility('private');

        $this->assertSame('archive', $input->getDisk());
        $this->assertSame('private', $input->getFileVisibility());

        $this->expectException(FormToolException::class);
        (new BluePrint())->file('invalid')->visibility('shared');
    }

    public function test_public_urls_use_the_selected_laravel_disk_url(): void
    {
        $this->assertSame(
            'https://cdn.example.test/storage/logo.png',
            FileManager::url('storage/logo.png', 'form-tool-public', 'public')
        );
    }

    public function test_private_urls_use_the_configured_resolver_and_expiry(): void
    {
        config(['form-tool.filesystem.privateUrlResolver' => TestPrivateFileUrlResolver::class]);

        $url = FileManager::url('storage/students/aadhaar.pdf', 'form-tool-public', 'private');

        $this->assertStringStartsWith(
            'https://app.example.test/private/form-tool-public%7Cstorage%2Fstudents%2Faadhaar.pdf?expires=',
            $url
        );
        $expires = (int) substr($url, strrpos($url, '=') + 1);
        $this->assertGreaterThanOrEqual(now()->addMinutes(4)->getTimestamp(), $expires);
        $this->assertLessThanOrEqual(now()->addMinutes(6)->getTimestamp(), $expires);
    }

    public function test_private_urls_fail_closed_without_a_resolver(): void
    {
        config(['form-tool.filesystem.privateUrlResolver' => null]);

        $this->expectException(FormToolException::class);
        $this->expectExceptionMessage('Private file URL resolver is not configured.');

        FileManager::url('storage/students/aadhaar.pdf', 'form-tool-public', 'private');
    }
}
