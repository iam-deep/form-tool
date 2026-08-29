<?php

namespace Deep\FormTool\Tests\Unit\Core;

require_once dirname(__DIR__, 2).'/TestCase.php';

use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Core\BulkAction;
use Deep\FormTool\Core\DataModel;
use Deep\FormTool\Core\Doc;
use Deep\FormTool\Core\EventType;
use Deep\FormTool\Exceptions\FormToolException;
use Deep\FormTool\Models\BaseModel;
use Deep\FormTool\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint as SchemaBlueprint;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Validation\Factory;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

class FormDuplicateFixture extends BaseModel
{
    public static $tableName = 'records';
    public static $primaryId = 'recordId';

    public static function add($data)
    {
        if (($data[static::$primaryId] ?? null) === 0) {
            unset($data[static::$primaryId]);
        }

        return parent::add($data);
    }
}

class FormDuplicateAuthUser extends Model
{
    public static function user(): object
    {
        return (object) ['id' => 7];
    }
}

class FormDuplicateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'form-tool.isGuarded' => false,
            'form-tool.isPreventForeignKeyDelete' => false,
            'form-tool.isLogActions' => false,
            'form-tool.auth' => [
                'isCustomAuth' => true,
                'userModel' => FormDuplicateAuthUser::class,
            ],
        ]);

        $this->app->instance('request', Request::create('/records/bulk-action', 'POST'));
        $this->app->instance('router', new Router($this->app['events'], $this->app));
        $validator = new Factory(new Translator(new ArrayLoader(), 'en'), $this->app);
        $validator->setPresenceVerifier(new DatabasePresenceVerifier($this->app['db']));
        $this->app->instance('validator', $validator);

        $this->database->getConnection()->getSchemaBuilder()->create('records', function (SchemaBlueprint $table) {
            $table->increments('recordId');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('code')->nullable();
            $table->string('section')->nullable();
            $table->unsignedInteger('schoolId')->nullable();
            $table->unsignedInteger('categoryId')->nullable();
            $table->unsignedInteger('rank')->nullable();
            $table->string('document')->nullable();
            $table->date('publishDate')->nullable();
            $table->text('publishDatesOnline')->nullable();
            $table->unsignedInteger('createdBy')->nullable();
            $table->unsignedInteger('updatedBy')->nullable();
            $table->dateTime('createdAt')->nullable();
            $table->dateTime('updatedAt')->nullable();
        });
        $this->database->getConnection()->getSchemaBuilder()->create('record_files', function (SchemaBlueprint $table) {
            $table->increments('fileId');
            $table->unsignedInteger('recordId');
            $table->string('attachment')->nullable();
        });

        config([
            'filesystems.default' => 'duplicate-files',
            'filesystems.disks.duplicate-files' => [
                'driver' => 'local',
                'root' => sys_get_temp_dir().'/form-tool-duplicate-'.uniqid('', true),
            ],
        ]);
        $this->app->singleton('filesystem', fn ($app) => new FilesystemManager($app));

        $this->resetFormToolAuth();
    }

    protected function tearDown(): void
    {
        $this->resetFormToolAuth();

        parent::tearDown();
    }

    public function test_crud_inherits_the_global_duplicate_setting(): void
    {
        config(['form-tool.isDuplicate' => false]);

        $crud = $this->makeCrud();

        $this->assertFalse($crud->isDuplicateEnabled());
        $this->assertArrayNotHasKey('duplicate', $crud->getTable()->bulkAction->getActions('normal'));
    }

    public function test_module_duplicate_setting_overrides_the_global_setting(): void
    {
        config(['form-tool.isDuplicate' => false]);
        $enabled = $this->makeCrud()->isDuplicate(true);

        config(['form-tool.isDuplicate' => true]);
        $disabled = $this->makeCrud()->isDuplicate(false);

        $this->assertTrue($enabled->isDuplicateEnabled());
        $this->assertArrayHasKey('duplicate', $enabled->getTable()->bulkAction->getActions('normal'));
        $this->assertFalse($disabled->isDuplicateEnabled());
        $this->assertArrayNotHasKey('duplicate', $disabled->getTable()->bulkAction->getActions('normal'));
    }

    public function test_disabled_module_rejects_a_submitted_duplicate_action(): void
    {
        DB::table('records')->insert(['recordId' => 1, 'name' => 'Original']);
        $redirect = new class
        {
            public array $errors = [];

            public function back(): static
            {
                return $this;
            }

            public function withErrors($errors): static
            {
                $this->errors = (array) $errors;

                return $this;
            }
        };
        $this->app->instance('redirect', $redirect);

        $crud = $this->makeCrud()->isDuplicate(false);
        $bulkAction = new BulkAction();
        $bulkAction->setTable($crud->getTable());
        $duplicate = new ReflectionMethod(BulkAction::class, 'duplicate');
        $duplicate->setAccessible(true);

        $response = $duplicate->invoke($bulkAction, [1]);

        $this->assertSame($redirect, $response);
        $this->assertSame(['Duplicating is disabled for this module.'], $redirect->errors);
        $this->assertSame(1, DB::table('records')->count());
    }

    public function test_on_duplicate_registers_a_pre_create_transformer(): void
    {
        $callback = static fn (array $data, object $original): array => $data;

        $crud = $this->makeCrud()->onDuplicate($callback);

        $this->assertSame($callback, $crud->getOnDuplicate());
    }

    public function test_duplicate_generates_incrementing_unique_text_and_slug_values(): void
    {
        DB::table('records')->insert([
            'recordId' => 1,
            'name' => 'Final Exam',
            'slug' => 'final-exam',
        ]);

        $crud = $this->makeCrud(function (BluePrint $input): void {
            $input->text('name', 'Name')->unique()->required();
            $input->text('slug', 'Slug')->slug()->required();
        });

        $this->assertTrue($crud->getForm()->validateDuplicateData([
            'name' => 'Final Exam Copy 1',
            'slug' => 'final-exam-copy-1',
        ]));

        $this->assertIsArray($this->duplicate($crud, 1));
        $this->assertIsArray($this->duplicate($crud, 1));

        $copies = DB::table('records')->where('recordId', '>', 1)->orderBy('recordId')->get();
        $this->assertSame('Final Exam Copy 1', $copies[0]->name, json_encode($copies));
        $this->assertSame('final-exam-copy-1', $copies[0]->slug);
        $this->assertSame('Final Exam Copy 2', $copies[1]->name);
        $this->assertSame('final-exam-copy-2', $copies[1]->slug);
    }

    public function test_on_duplicate_transforms_data_before_create_validation(): void
    {
        DB::table('records')->insert(['recordId' => 1, 'name' => 'Original']);

        $seenOriginal = null;
        $crud = $this->makeCrud(function (BluePrint $input): void {
            $input->text('name', 'Name')->unique()->required();
        })->onDuplicate(function (array $data, object $original) use (&$seenOriginal): array {
            $seenOriginal = $original;
            $data['name'] = 'Callback Copy';

            return $data;
        });

        $this->assertTrue($crud->getForm()->validateDuplicateData(['name' => 'Callback Copy']));

        $this->assertIsArray($this->duplicate($crud, 1));

        $this->assertSame('Original', $seenOriginal->name);
        $this->assertSame(
            'Callback Copy',
            DB::table('records')->where('recordId', 2)->value('name'),
            json_encode(DB::table('records')->get())
        );
    }

    public function test_on_duplicate_result_is_validated_as_a_new_create(): void
    {
        DB::table('records')->insert(['recordId' => 1, 'name' => 'Original']);

        $crud = $this->makeCrud(function (BluePrint $input): void {
            $input->text('name', 'Name')->required();
        })->onDuplicate(function (array $data): array {
            $data['name'] = null;

            return $data;
        });

        $result = $this->duplicate($crud, 1);

        $this->assertFalse($result);
        $this->assertSame(1, DB::table('records')->count());
    }

    public function test_numeric_only_composite_unique_requires_an_on_duplicate_callback(): void
    {
        DB::table('records')->insert([
            'recordId' => 1,
            'categoryId' => 10,
            'rank' => 1,
        ]);

        $crud = $this->makeCrud(function (BluePrint $input): void {
            $input->number('categoryId', 'Category')->required();
            $input->number('rank', 'Rank')->required();
        })->unique(['categoryId', 'rank']);

        $this->expectException(FormToolException::class);
        $this->expectExceptionMessage('onDuplicate()');

        $this->duplicate($crud, 1);
    }

    public function test_on_duplicate_must_return_an_array(): void
    {
        DB::table('records')->insert(['recordId' => 1, 'name' => 'Original']);
        $crud = $this->makeCrud()->onDuplicate(fn () => 'invalid');

        $this->expectException(FormToolException::class);
        $this->expectExceptionMessage('onDuplicate() must return an array.');

        $this->duplicate($crud, 1);
    }

    public function test_unique_copy_generation_respects_the_unique_scope(): void
    {
        DB::table('records')->insert([
            ['recordId' => 1, 'schoolId' => 1, 'name' => 'Final Exam'],
            ['recordId' => 2, 'schoolId' => 2, 'name' => 'Final Exam Copy 1'],
        ]);
        $crud = $this->makeCrud(function (BluePrint $input): void {
            $input->number('schoolId', 'School');
            $input->text('name', 'Name')->unique(function ($query): void {
                $query->where('schoolId', 1);
            });
        });

        $this->duplicate($crud, 1);

        $this->assertSame('Final Exam Copy 1', DB::table('records')->where('recordId', 3)->value('name'));
    }

    public function test_composite_unique_changes_the_last_text_field(): void
    {
        DB::table('records')->insert([
            'recordId' => 1,
            'categoryId' => 10,
            'section' => 'A',
        ]);
        $crud = $this->makeCrud(function (BluePrint $input): void {
            $input->number('categoryId', 'Category')->required();
            $input->text('section', 'Section')->required();
        })->unique(['categoryId', 'section']);

        $this->duplicate($crud, 1);
        $this->duplicate($crud, 1);

        $sections = DB::table('records')->where('recordId', '>', 1)->orderBy('recordId')->pluck('section')->all();
        $this->assertSame(['A Copy 1', 'A Copy 2'], $sections);
    }

    public function test_nullable_unique_text_remains_null_when_it_does_not_conflict(): void
    {
        DB::table('records')->insert(['recordId' => 1, 'name' => 'Original', 'code' => null]);
        $crud = $this->makeCrud(function (BluePrint $input): void {
            $input->text('name', 'Name');
            $input->text('code', 'Code')->unique();
        });

        $this->duplicate($crud, 1);

        $this->assertNull(DB::table('records')->where('recordId', 2)->value('code'));
    }

    public function test_duplicate_runs_callback_validation_with_store_type(): void
    {
        DB::table('records')->insert(['recordId' => 1, 'name' => 'Original']);
        $validationType = null;
        $crud = $this->makeCrud(function (BluePrint $input): void {
            $input->text('name', 'Name')->required();
        })->onDuplicate(function (array $data): array {
            $data['name'] = 'Rejected';

            return $data;
        })->callbackValidation(function ($request, $type) use (&$validationType) {
            $validationType = $type;

            return $request->post('name') === 'Allowed' ? true : 'Duplicate name rejected.';
        });

        $this->assertFalse($this->duplicate($crud, 1));
        $this->assertSame('store', $validationType);
        $this->assertSame(1, DB::table('records')->count());
    }

    public function test_duplicate_formats_a_stored_date_for_create_validation(): void
    {
        DB::table('records')->insert([
            'recordId' => 1,
            'name' => 'Original',
            'publishDate' => '2026-08-29',
        ]);
        $crud = $this->makeCrud(function (BluePrint $input): void {
            $input->text('name', 'Name');
            $input->date('publishDate', 'Final Result Publish Date')->required();
        });

        $this->assertIsArray($this->duplicate($crud, 1));
        $this->assertSame('2026-08-29', DB::table('records')->where('recordId', 2)->value('publishDate'));
    }

    public function test_duplicate_preserves_multiple_select_values_inside_json_rows(): void
    {
        DB::table('records')->insert([
            'recordId' => 1,
            'name' => 'Original',
            'publishDatesOnline' => json_encode([
                ['classIds' => json_encode([1, 2]), 'date' => '2026-08-29'],
            ]),
        ]);
        $crud = $this->makeCrud(function (BluePrint $input): void {
            $input->text('name', 'Name');
            $input->multiple('publishDatesOnline', 'Online Publish Date', function (BluePrint $row): void {
                $row->select('classIds', 'Classes')->options([1 => 'One', 2 => 'Two'])->multiple()->required();
                $row->date('date', 'Date')->required();
            });
        });

        $this->assertIsArray($this->duplicate($crud, 1));

        $copy = json_decode(DB::table('records')->where('recordId', 2)->value('publishDatesOnline'), true);
        $this->assertSame([1, 2], json_decode($copy[0]['classIds'], true));
        $this->assertSame('2026-08-29', $copy[0]['date']);
    }

    public function test_duplicate_copies_parent_and_multiple_table_files(): void
    {
        Storage::disk('duplicate-files')->put('storage/source.txt', 'parent file');
        Storage::disk('duplicate-files')->put('storage/child.txt', 'child file');
        DB::table('records')->insert([
            'recordId' => 1,
            'name' => 'Original',
            'document' => 'storage/source.txt',
        ]);
        DB::table('record_files')->insert([
            'recordId' => 1,
            'attachment' => 'storage/child.txt',
        ]);

        $crud = $this->makeCrud(function (BluePrint $input): void {
            $input->text('name', 'Name');
            $input->file('document', 'Document')->disk('duplicate-files')->visibility('private');
            $input->multiple('files', 'Files', function (BluePrint $file): void {
                $file->file('attachment', 'Attachment')->disk('duplicate-files')->visibility('private');
            })->table('record_files', 'fileId', 'recordId', '');
        });

        $this->duplicate($crud, 1);

        $parentCopy = DB::table('records')->where('recordId', 2)->value('document');
        $childCopy = DB::table('record_files')->where('recordId', 2)->value('attachment');
        $this->assertNotSame('storage/source.txt', $parentCopy);
        $this->assertNotSame('storage/child.txt', $childCopy);
        $this->assertSame('parent file', Storage::disk('duplicate-files')->get($parentCopy));
        $this->assertSame('child file', Storage::disk('duplicate-files')->get($childCopy));
    }

    public function test_missing_source_file_prevents_the_duplicate(): void
    {
        DB::table('records')->insert([
            'recordId' => 1,
            'name' => 'Original',
            'document' => 'storage/missing.txt',
        ]);
        $crud = $this->makeCrud(function (BluePrint $input): void {
            $input->text('name', 'Name');
            $input->file('document', 'Document')->disk('duplicate-files')->visibility('private');
        });

        try {
            $this->duplicate($crud, 1);
            $this->fail('Expected the missing source file to fail duplication.');
        } catch (FormToolException $exception) {
            $this->assertStringContainsString('storage/missing.txt', $exception->getMessage());
        }

        $this->assertSame(1, DB::table('records')->count());
    }

    public function test_event_failure_rolls_back_rows_and_removes_copied_files(): void
    {
        Storage::disk('duplicate-files')->put('storage/source.txt', 'parent file');
        DB::table('records')->insert([
            'recordId' => 1,
            'name' => 'Original',
            'document' => 'storage/source.txt',
        ]);
        $crud = $this->makeCrud(function (BluePrint $input): void {
            $input->text('name', 'Name');
            $input->file('document', 'Document')->disk('duplicate-files')->visibility('private');
        })->onEvent(EventType::DUPLICATE, function (): void {
            throw new RuntimeException('Duplicate event failed.');
        });

        try {
            $this->duplicate($crud, 1);
            $this->fail('Expected duplicate event failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Duplicate event failed.', $exception->getMessage());
        }

        $this->assertSame(1, DB::table('records')->count());
        $this->assertSame(['storage/source.txt'], Storage::disk('duplicate-files')->allFiles());
    }

    public function test_duplicate_invokes_only_the_duplicate_event(): void
    {
        DB::table('records')->insert(['recordId' => 1, 'name' => 'Original']);
        $events = [];
        $crud = $this->makeCrud()->onEvent(EventType::ALL, function ($id, $data, EventType $event) use (&$events): void {
            $events[] = $event;
        });

        $this->duplicate($crud, 1);

        $this->assertSame([EventType::DUPLICATE], $events);
    }

    private function makeCrud(?callable $fields = null)
    {
        return Doc::create(
            $this->resource(),
            new DataModel(FormDuplicateFixture::class),
            function (BluePrint $input) use ($fields): void {
                if ($fields) {
                    $fields($input);

                    return;
                }

                $input->text('name', 'Name');
            }
        )->softDelete(false)->wantsArray();
    }

    private function duplicate($crud, int $id)
    {
        $bulkAction = new BulkAction();
        $bulkAction->setTable($crud->getTable());
        $duplicate = new ReflectionMethod(BulkAction::class, 'doDuplicate');
        $duplicate->setAccessible(true);

        return $duplicate->invoke($bulkAction, $id, []);
    }

    private function resource(): object
    {
        return (object) [
            'title' => 'Records',
            'route' => 'records',
            'singularTitle' => 'Record',
        ];
    }

    private function resetFormToolAuth(): void
    {
        $reflection = new ReflectionClass(\Deep\FormTool\Core\Auth::class);
        foreach (['config', 'user'] as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue(null, null);
        }
    }
}
