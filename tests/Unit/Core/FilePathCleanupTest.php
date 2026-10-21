<?php

namespace Deep\FormTool\Tests\Unit\Core;

use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Core\Crud;
use Deep\FormTool\Core\DataModel;
use Deep\FormTool\Core\Form;
use Deep\FormTool\Core\InputTypes\FileType;
use Deep\FormTool\Core\InputTypes\ImageType;
use Deep\FormTool\Exceptions\FileUploadException;
use Deep\FormTool\Support\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Mockery;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class FilePathCleanupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['form-tool.filesystem.disk' => 'path-tests', 'form-tool.filesystem.visibility' => 'public', 'filesystems.disks.path-tests.driver' => 'local',
            'form-tool.uploadPath' => 'storage', 'form-tool.uploadSubDirFormat' => '', 'form-tool.isLogActions' => false]);
        Storage::fake('path-tests');
    }

    public function test_retained_file_and_image_paths_have_a_250_character_maximum(): void
    {
        $this->app->instance('request', Request::create('/files', 'PUT'));
        foreach ([new FileType(), new ImageType()] as $input) {
            $input->setDbField('document');
            foreach ([250 => true, 251 => false] as $length => $expected) {
                $this->assertSame($expected, Validator::make(['document' => str_repeat('a', $length)],
                    ['document' => $input->getValidations('update')])->passes());
            }
        }
    }

    public function test_generated_path_is_checked_before_writing_and_existing_files_are_kept(): void
    {
        $disk = Storage::disk('path-tests');
        $disk->put('storage/existing.txt', 'Keep me');
        try {
            FileManager::uploadFile(UploadedFile::fake()->createWithContent('notes.txt', 'notes'),
                str_repeat('long/', 50));
            $this->fail('An overlong path was accepted.');
        } catch (FileUploadException $exception) {
            $this->assertStringContainsString('250', $exception->getMessage());
        }
        $this->assertSame(['storage/existing.txt'], $disk->allFiles());
    }

    public function test_a_second_failed_upload_cleans_the_first_upload_when_creating(): void
    {
        [$form, $model, $blueprint] = $this->form();
        $blueprint->file('second')->path(str_repeat('long/', 50));
        request()->files->set('second', UploadedFile::fake()->createWithContent('second.txt', 'second'));
        $model->shouldNotReceive('add');
        try {
            $form->store();
            $this->fail('Expected an upload failure.');
        } catch (FileUploadException $exception) {
            $this->assertStringContainsString('250', $exception->getMessage());
        }
        $this->assertSame([], Storage::disk('path-tests')->allFiles());
    }

    public function test_partial_upload_is_cleaned_when_setting_visibility_fails(): void
    {
        $disk = Mockery::mock(Storage::disk('path-tests'));
        $disk->shouldReceive('setVisibility')->once()->andThrow(new RuntimeException('Visibility failed'));
        Storage::shouldReceive('disk')->with('path-tests')->andReturn($disk);
        try {
            FileManager::uploadFile(UploadedFile::fake()->createWithContent('notes.txt', 'notes'), '');
            $this->fail('Expected an upload failure.');
        } catch (FileUploadException $exception) {
            $this->assertSame('Visibility failed', $exception->getMessage());
        }
        $this->assertSame([], $disk->allFiles());
    }

    public function test_path_limit_is_independent_from_upload_size_and_can_be_overridden(): void
    {
        $this->app->instance('request', Request::create('/files', 'POST', [], [], [
            'document' => UploadedFile::fake()->createWithContent('notes.txt', 'notes'),
        ]));
        $input = (new FileType())->maxUploadSize(2048)->maxPathLength(300);
        $input->setDbField('document');
        $this->assertContains('max:2048', $input->getValidations('store'));
        $this->assertNotContains('max:250', $input->getValidations('store'));

        $this->app->instance('request', Request::create('/files', 'PUT'));
        $this->assertTrue(Validator::make(['document' => str_repeat('a', 300)],
            ['document' => $input->getValidations('update')])->passes());
        $this->assertFalse(Validator::make(['document' => str_repeat('a', 301)],
            ['document' => $input->getValidations('update')])->passes());
    }

    public function test_create_database_failure_and_false_result_clean_new_uploads(): void
    {
        foreach ([false, new RuntimeException('Database failure')] as $result) {
            [$form, $model] = $this->form();
            $expectation = $model->shouldReceive('add')->once();
            $result instanceof RuntimeException ? $expectation->andThrow($result) : $expectation->andReturn($result);
            try {
                $form->store();
                $this->assertFalse($result);
            } catch (RuntimeException $exception) {
                $this->assertSame('Database failure', $exception->getMessage());
            }
            $this->assertSame([], Storage::disk('path-tests')->allFiles());
        }
    }

    public function test_failed_update_keeps_the_original_and_cleans_the_replacement(): void
    {
        Storage::disk('path-tests')->put('storage/original.txt', 'Original');
        [$form, $model] = $this->form();
        request()->merge(['document' => 'storage/original.txt']);
        $model->shouldReceive('getOne')->andReturn((object) ['id' => 1, 'document' => 'storage/original.txt']);
        $model->shouldReceive('updateOne')->once()->andThrow(new RuntimeException('Update failure'));
        try {
            $form->update(1);
            $this->fail('Expected a database failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Update failure', $exception->getMessage());
        }
        $this->assertSame(['storage/original.txt'], Storage::disk('path-tests')->allFiles());
    }

    public function test_failure_after_persistence_does_not_delete_a_saved_file(): void
    {
        [$form, $model] = $this->form();
        $model->shouldReceive('add')->once()->andReturn(1);
        $model->shouldReceive('getOne')->andThrow(new RuntimeException('Post-save failure'));
        try {
            $form->store();
            $this->fail('Expected a post-save failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Post-save failure', $exception->getMessage());
        }
        $this->assertCount(1, Storage::disk('path-tests')->allFiles());
    }

    public function test_multiple_row_uploads_are_cleaned_on_failure_and_originals_only_removed_on_success(): void
    {
        foreach ([false, true] as $success) {
            Storage::fake('path-tests');
            $disk = Storage::disk('path-tests');
            $disk->put('storage/old-a.txt', 'A');
            $disk->put('storage/old-b.txt', 'B');
            [$form, $model, $blueprint] = $this->form();
            $blueprint->multiple('items', 'Items', function (BluePrint $input) {
                $input->file('attachment')->path('items');
                $input->text('label');
            });
            $form->setFormStatusUpdate(1);
            request()->merge(['items' => [['attachment' => 'storage/old-a.txt'], ['attachment' => 'storage/old-b.txt']]]);
            request()->files->set('items', [
                ['attachment' => UploadedFile::fake()->createWithContent('a.txt', 'New A')],
                ['attachment' => UploadedFile::fake()->createWithContent('b.txt', 'New B')],
            ]);
            $expectation = $model->shouldReceive('updateOne')->once();
            $success ? $expectation->andReturn(1) : $expectation->andThrow(new RuntimeException('Multiple save failed'));
            try {
                (new ReflectionMethod(Form::class, 'saveMultipleFields'))->invoke($form);
                $this->assertTrue($success);
            } catch (RuntimeException $exception) {
                $this->assertFalse($success);
                $this->assertSame('Multiple save failed', $exception->getMessage());
            }

            $expected = $success ? ['storage/items/a.txt', 'storage/items/b.txt'] : ['storage/old-a.txt', 'storage/old-b.txt'];
            $this->assertSame($expected, $disk->allFiles());
        }
    }

    public function test_a_generated_path_of_exactly_250_characters_is_accepted(): void
    {
        $path = FileManager::uploadFile(UploadedFile::fake()->createWithContent(str_repeat('a', 38).'.txt', 'notes'),
            str_repeat('x/', 100));
        $this->assertSame(250, mb_strlen($path));
        Storage::disk('path-tests')->assertExists($path);
    }

    private function form(): array
    {
        $this->app->instance('request', Request::create('/files', 'POST', [], [], [
            'document' => UploadedFile::fake()->createWithContent('notes.txt', 'notes'),
        ], ['HTTP_ACCEPT' => 'application/json']));
        $blueprint = new BluePrint();
        $model = Mockery::mock(DataModel::class);
        $model->shouldReceive('getPrimaryId')->andReturn('id');
        $form = new class((object) ['route' => 'files', 'singularTitle' => 'File'], $blueprint, $model) extends Form
        {
            public function validate()
            {
                return true;
            }
        };
        $form->setCrud(new Crud());
        $blueprint->setForm($form);
        $blueprint->file('document')->path('notes');

        return [$form, $model, $blueprint];
    }
}
