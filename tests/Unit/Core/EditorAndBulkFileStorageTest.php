<?php

namespace Deep\FormTool\Tests\Unit\Core;

use Deep\FormTool\Core\BulkAction;
use Deep\FormTool\Core\InputTypes\EditorType;
use Deep\FormTool\Core\InputTypes\FileType;
use Deep\FormTool\Exceptions\FormToolException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EditorAndBulkFileStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('form-tool-content');
        config([
            'form-tool.filesystem.disk' => 'local',
            'form-tool.filesystem.visibility' => 'public',
        ]);
    }

    public function test_editor_upload_uses_its_disk_visibility_and_disk_url(): void
    {
        $editor = (new EditorType())
            ->disk('form-tool-content')
            ->visibility('public');
        $request = Request::create('/form-tool/upload?path=editor', 'POST');
        $request->files->set('upload', UploadedFile::fake()->image('notice.png', 40, 20));

        $response = $editor->uploadImage($request);
        $payload = $response->getData(true);

        $this->assertSame('form-tool-content', $editor->getDisk());
        $this->assertSame('public', $editor->getFileVisibility());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('/storage/storage/editor/', $payload['url']);
        Storage::disk('form-tool-content')->assertExists(
            preg_replace('#^/storage/#', '', $payload['url'])
        );
    }

    public function test_remote_editor_requires_explicit_visibility(): void
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

        (new EditorType())->getFileVisibility();
    }

    public function test_bulk_file_copy_uses_the_file_fields_disk_and_visibility(): void
    {
        Storage::disk('form-tool-content')->put('storage/source.txt', 'source');
        $field = (new FileType())
            ->disk('form-tool-content')
            ->visibility('private');

        $bulkAction = new class extends BulkAction
        {
            public function copyField(FileType $field, string $value): ?string
            {
                return $this->copyFileValue($field, $value);
            }
        };

        $copy = $bulkAction->copyField($field, 'storage/source.txt');

        $this->assertSame('storage/source_2.txt', $copy);
        Storage::disk('form-tool-content')->assertExists($copy);
        $this->assertSame('source', Storage::disk('form-tool-content')->get($copy));
    }

    public function test_editor_route_overrides_require_a_valid_signature(): void
    {
        $signedUrl = URL::signedRoute('form-tool.upload_image', [
            'path' => 'school-info',
            'disk' => 'form-tool-content',
            'visibility' => 'public',
        ]);
        $request = Request::create($signedUrl, 'POST');
        $request->files->set('upload', UploadedFile::fake()->image('public.png', 20, 20));

        $response = (new EditorType())->uploadImage($request);

        $this->assertSame(200, $response->getStatusCode());
        Storage::disk('form-tool-content')->assertExists('storage/school-info/'.date('m-Y').'/public.png');

        $tampered = Request::create($signedUrl.'&visibility=private', 'POST');
        $tampered->files->set('upload', UploadedFile::fake()->image('private.png', 20, 20));

        try {
            (new EditorType())->uploadImage($tampered);
            $this->fail('Expected tampered editor storage options to be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }
}
