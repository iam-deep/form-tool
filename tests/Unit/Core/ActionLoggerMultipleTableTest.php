<?php

namespace Deep\FormTool\Tests\Unit\Core;

require_once dirname(__DIR__, 2).'/TestCase.php';

use Deep\FormTool\Core\Auth as FormToolAuth;
use Deep\FormTool\Core\ActionLogger;
use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Core\BulkAction;
use Deep\FormTool\Core\DataModel;
use Deep\FormTool\Core\Doc;
use Deep\FormTool\Core\InputTypes\Common\CrudState;
use Deep\FormTool\Models\BaseModel;
use Deep\FormTool\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint as SchemaBlueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Validation\Factory;
use ReflectionClass;
use ReflectionMethod;

class ActionLoggerMultipleTableFixture extends BaseModel
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

class ActionLoggerMultipleTableAuthUser extends Model
{
    public static function user(): object
    {
        return (object) ['id' => 7];
    }
}

class ActionLoggerMultipleTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $schema = $this->database->getConnection()->getSchemaBuilder();
        $schema->create('records', function (SchemaBlueprint $table) {
            $table->increments('recordId');
            $table->text('jsonParts')->nullable();
            $table->unsignedInteger('createdBy')->nullable();
            $table->dateTime('createdAt')->nullable();
            $table->unsignedInteger('updatedBy')->nullable();
            $table->dateTime('updatedAt')->nullable();
        });
        $schema->create('record_parts', function (SchemaBlueprint $table) {
            $table->increments('partId');
            $table->unsignedInteger('recordId');
            $table->string('name');
            $table->unsignedInteger('enabled');
            $table->foreign('recordId')->references('recordId')->on('records')->cascadeOnDelete();
        });
        $schema->create('action_logs', function (SchemaBlueprint $table) {
            $table->increments('id');
            $table->string('action');
            $table->integer('refId');
            $table->string('token')->nullable();
            $table->string('description')->nullable();
            $table->text('data')->nullable();
            $table->string('path')->nullable();
            $table->string('module');
            $table->string('route');
            $table->string('ipAddress')->nullable();
            $table->string('userAgent')->nullable();
            $table->string('createdByName')->nullable();
            $table->unsignedInteger('createdBy')->nullable();
            $table->dateTime('createdAt')->nullable();
        });

        config(['form-tool.auth' => [
            'isCustomAuth' => true,
            'userModel' => ActionLoggerMultipleTableAuthUser::class,
        ], 'form-tool.isPreventForeignKeyDelete' => false]);

        $this->app->instance('auth', new class
        {
            public function user(): object
            {
                return (object) ['id' => 7, 'name' => 'Test User'];
            }

            public function id(): int
            {
                return 7;
            }
        });
        $this->app->instance('router', new Router($this->app['events'], $this->app));
        $validator = new Factory(new Translator(new ArrayLoader(), 'en'), $this->app);
        $validator->setPresenceVerifier(new DatabasePresenceVerifier($this->app['db']));
        $this->app->instance('validator', $validator);

        $this->resetFormToolAuth();
    }

    protected function tearDown(): void
    {
        Doc::setState(CrudState::NONE);
        $this->resetFormToolAuth();

        parent::tearDown();
    }

    public function test_create_logs_json_and_separate_table_multiple_fields(): void
    {
        $crud = $this->makeCrud(Request::create('/records', 'POST', [
            'method' => 'CREATE',
            'redirect' => '/records',
            'jsonParts' => [['name' => 'JSON part', 'enabled' => '1']],
            'tableParts' => [['name' => 'Table part', 'enabled' => '0']],
        ]));

        $response = $crud->store();

        $this->assertTrue($response['status']);
        $data = json_decode(DB::table('action_logs')->where('action', 'create')->value('data'), true);

        $this->assertSame($this->tableLog([], [
            ['Name' => 'JSON part', 'Enabled' => ['type' => 'text', 'data' => 'Yes']],
        ]), $data['data']['JSON Parts']);
        $this->assertSame($this->tableLog([], [
            ['Name' => 'Table part', 'Enabled' => ['type' => 'text', 'data' => 'No']],
        ]), $data['data']['Table Parts']);
    }

    public function test_update_logs_old_and_new_rows_for_both_multiple_storage_modes(): void
    {
        DB::table('records')->insert([
            'recordId' => 1,
            'jsonParts' => json_encode([['name' => 'Old JSON', 'enabled' => 1]]),
        ]);
        DB::table('record_parts')->insert([
            'recordId' => 1,
            'name' => 'Old table',
            'enabled' => 0,
        ]);

        $crud = $this->makeCrud(Request::create('/records/1', 'PUT', [
            'jsonParts' => [['name' => 'New JSON', 'enabled' => '0']],
            'tableParts' => [['name' => 'New table', 'enabled' => '1']],
        ]));

        $response = $crud->update(1);

        $this->assertTrue($response['status']);
        $data = json_decode(DB::table('action_logs')->where('action', 'update')->value('data'), true);

        $this->assertSame($this->tableLog(
            [['Name' => 'Old JSON', 'Enabled' => ['type' => 'text', 'data' => 'Yes']]],
            [['Name' => 'New JSON', 'Enabled' => ['type' => 'text', 'data' => 'No']]],
        ), $data['data']['JSON Parts']);
        $this->assertSame($this->tableLog(
            [['Name' => 'Old table', 'Enabled' => ['type' => 'text', 'data' => 'No']]],
            [['Name' => 'New table', 'Enabled' => ['type' => 'text', 'data' => 'Yes']]],
        ), $data['data']['Table Parts']);
    }

    public function test_destroy_logs_removed_rows_for_both_multiple_storage_modes(): void
    {
        DB::table('records')->insert([
            'recordId' => 1,
            'jsonParts' => json_encode([['name' => 'JSON part', 'enabled' => 1]]),
        ]);
        DB::table('record_parts')->insert([
            'recordId' => 1,
            'name' => 'Table part',
            'enabled' => 0,
        ]);

        $crud = $this->makeCrud(Request::create('/records/1', 'DELETE'));

        $response = $crud->destroy(1);

        $this->assertTrue($response['status']);
        $data = json_decode(DB::table('action_logs')->where('action', 'destroy')->value('data'), true);

        $this->assertSame($this->tableLog(
            [['Name' => 'JSON part', 'Enabled' => ['type' => 'text', 'data' => 'Yes']]],
            [],
        ), $data['data']['JSON Parts']);
        $this->assertSame($this->tableLog(
            [['Name' => 'Table part', 'Enabled' => ['type' => 'text', 'data' => 'No']]],
            [],
        ), $data['data']['Table Parts']);
    }

    public function test_duplicate_logs_copied_rows_for_both_multiple_storage_modes(): void
    {
        DB::table('records')->insert([
            'recordId' => 2,
            'jsonParts' => json_encode([['name' => 'JSON copy', 'enabled' => 0]]),
        ]);
        DB::table('record_parts')->insert([
            'recordId' => 2,
            'name' => 'Table copy',
            'enabled' => 1,
        ]);

        $crud = $this->makeCrud(Request::create('/records/duplicate', 'POST'));
        $bulkAction = new BulkAction();
        $bulkAction->setTable($crud->getTable());
        $duplicate = new ReflectionMethod(BulkAction::class, 'doDuplicate');
        $duplicate->setAccessible(true);

        $result = $duplicate->invoke($bulkAction, 2, [
            'updatedBy' => null,
            'updatedAt' => null,
            'createdBy' => 7,
            'createdAt' => '2026-08-27 10:00:00',
        ]);

        $this->assertIsArray($result);
        $newId = DB::table('records')->max('recordId');
        $this->assertSame(1, DB::table('record_parts')->where('recordId', $newId)->count());
        $data = json_decode(DB::table('action_logs')->where('action', 'duplicate')->value('data'), true);
        $this->assertSame($this->tableLog([], [
            ['Name' => 'JSON copy', 'Enabled' => ['type' => 'text', 'data' => 'No']],
        ]), $data['data']['JSON Parts']);
        $this->assertSame($this->tableLog([], [
            ['Name' => 'Table copy', 'Enabled' => ['type' => 'text', 'data' => 'Yes']],
        ]), $data['data']['Table Parts']);
    }

    public function test_table_diff_keeps_an_identifier_only_change_when_duplicate_identifiers_require_position_matching(): void
    {
        $diff = ActionLogger::getMultipleTableDiff(
            ['Name', 'Value'],
            [
                ['Name' => 'Duplicate', 'Value' => 'Same'],
                ['Name' => 'Duplicate', 'Value' => 'Same'],
            ],
            [
                ['Name' => 'Duplicate', 'Value' => 'Same'],
                ['Name' => 'Changed', 'Value' => 'Same'],
            ],
        );

        $this->assertSame(['Name'], $diff['columns']);
        $this->assertSame([[
            'type' => 'update',
            'cells' => [
                'Name' => [
                    'old' => 'Duplicate',
                    'new' => 'Changed',
                    'identifier' => true,
                ],
            ],
        ]], $diff['rows']);
    }

    public function test_table_diff_marks_added_and_removed_rows(): void
    {
        $diff = ActionLogger::getMultipleTableDiff(
            ['Name', 'Value'],
            [
                ['Name' => 'Removed', 'Value' => 'Old'],
                ['Name' => 'Kept', 'Value' => 'Same'],
            ],
            [
                ['Name' => 'Kept', 'Value' => 'Same'],
                ['Name' => 'Added', 'Value' => 'New'],
            ],
        );

        $this->assertSame(['add', 'remove'], array_column($diff['rows'], 'type'));
        $this->assertSame(['Name', 'Value'], $diff['columns']);
    }

    public function test_table_diff_uses_position_when_a_formatted_identifier_is_blank(): void
    {
        $oldIdentifier = ['type' => 'text', 'data' => ''];
        $newIdentifier = ['type' => 'text', 'data' => 'Filled'];

        $diff = ActionLogger::getMultipleTableDiff(
            ['Name', 'Value'],
            [['Name' => $oldIdentifier, 'Value' => 'Same']],
            [['Name' => $newIdentifier, 'Value' => 'Same']],
        );

        $this->assertSame(['Name'], $diff['columns']);
        $this->assertSame('update', $diff['rows'][0]['type']);
        $this->assertSame([
            'old' => $oldIdentifier,
            'new' => $newIdentifier,
            'identifier' => true,
        ], $diff['rows'][0]['cells']['Name']);
    }

    public function test_table_diff_ignores_reordering_when_unique_row_values_are_unchanged(): void
    {
        $diff = ActionLogger::getMultipleTableDiff(
            ['Name', 'Value'],
            [
                ['Name' => 'First', 'Value' => 'A'],
                ['Name' => 'Second', 'Value' => 'B'],
            ],
            [
                ['Name' => 'Second', 'Value' => 'B'],
                ['Name' => 'First', 'Value' => 'A'],
            ],
        );

        $this->assertSame([], $diff['columns']);
        $this->assertSame([], $diff['rows']);
    }

    private function makeCrud(Request $request)
    {
        $this->app->instance('request', $request);
        Doc::setState($request->isMethod('PUT') ? CrudState::UPDATE : CrudState::STORE);

        $crud = Doc::create($this->resource(), new DataModel(ActionLoggerMultipleTableFixture::class), function (BluePrint $input) {
            $input->multiple('jsonParts', 'JSON Parts', function (BluePrint $part) {
                $this->addPartFields($part);
            });
            $input->multiple('tableParts', 'Table Parts', function (BluePrint $part) {
                $this->addPartFields($part);
            })->table('record_parts', 'partId', 'recordId', '');
        });
        $crud->softDelete(false)->wantsArray();

        return $crud;
    }

    private function addPartFields(BluePrint $part): void
    {
        $part->text('name', 'Name');
        $part->select('enabled', 'Enabled')->options([1 => 'Yes', 0 => 'No'])->noFirst();
    }

    private function tableLog(array $oldRows, array $newRows): array
    {
        return [
            'type' => 'table',
            'columns' => ['Name', 'Enabled'],
            'data' => [$oldRows, $newRows],
        ];
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
        $reflection = new ReflectionClass(FormToolAuth::class);
        foreach (['config', 'user'] as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue(null, null);
        }
    }
}
