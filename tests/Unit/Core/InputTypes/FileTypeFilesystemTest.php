<?php

namespace Deep\FormTool\Tests\Unit\Core\InputTypes;

use DateTimeInterface;
use Deep\FormTool\Contracts\PrivateFileUrlResolver;
use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Exceptions\FormToolException;
use Deep\FormTool\Support\FileManager;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
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
            'filesystems.disks.form-tool-cache' => [
                'driver' => 'local',
                'root' => storage_path('framework/testing/disks/form-tool-cache'),
                'url' => 'https://app.example.test/local-cache',
                'visibility' => 'public',
            ],
            'form-tool.filesystem.disk' => 'form-tool-public',
            'form-tool.filesystem.visibility' => 'public',
            'form-tool.filesystem.privateUrlTtlMinutes' => 5,
            'form-tool.imageCacheDisk' => 'form-tool-cache',
            'form-tool.imageCachePath' => 'cache',
            'form-tool.imageCacheWidth' => 20,
            'form-tool.imageCacheHeight' => 20,
        ]);
        Storage::forgetDisk('form-tool-public');
        Storage::forgetDisk('form-tool-cache');
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

    public function test_remote_file_field_requires_explicit_visibility(): void
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

        (new BluePrint())->file('document')->getFileVisibility();
    }

    public function test_public_urls_use_the_selected_laravel_disk_url(): void
    {
        $this->assertSame(
            'https://cdn.example.test/storage/logo.png',
            FileManager::url('storage/logo.png', 'form-tool-public', 'public')
        );
    }

    public function test_public_image_field_generates_and_uses_a_local_derivative(): void
    {
        $path = 'storage/students/public-photo.png';
        Storage::disk('form-tool-public')->put($path, (string) Image::canvas(80, 40)->encode('png'));
        Storage::disk('form-tool-cache')->deleteDirectory('cache');

        $input = (new BluePrint())
            ->image('photo')
            ->disk('form-tool-public')
            ->visibility('public');

        $html = $input->getNiceValue($path);

        $this->assertStringContainsString(
            'src="https://app.example.test/local-cache/cache/storage/students/public-photo-20x20.png"',
            $html
        );
        Storage::disk('form-tool-cache')->assertExists('cache/storage/students/public-photo-20x20.png');
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

    public function test_private_file_fields_log_filenames_as_text_without_file_payloads(): void
    {
        $input = (new BluePrint())
            ->file('receipt')
            ->disk('form-tool-public')
            ->visibility('private')
            ->setValue('storage/accounts/receipt.pdf');

        $this->assertSame(
            'receipt.pdf',
            $input->getLoggerValue('create')
        );

        $this->assertSame(
            [
                'type' => 'text',
                'data' => [
                    'old-receipt.pdf',
                    'receipt.pdf',
                ],
            ],
            $input->getLoggerValue('update', 'storage/accounts/old-receipt.pdf')
        );
    }

    public function test_public_file_fields_keep_file_payloads_in_action_logs(): void
    {
        $input = (new BluePrint())
            ->file('receipt')
            ->disk('form-tool-public')
            ->visibility('public')
            ->setValue('storage/accounts/receipt.pdf');

        $this->assertSame(
            [
                'type' => 'file',
                'data' => 'storage/accounts/receipt.pdf',
            ],
            $input->getLoggerValue('create')
        );
    }
}
