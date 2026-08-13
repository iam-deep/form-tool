<?php

namespace Deep\FormTool\Tests\Unit\Core;

use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Core\DataModel;
use Deep\FormTool\Core\Doc;
use Deep\FormTool\Models\BaseModel;
use Deep\FormTool\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint as SchemaBlueprint;
use Illuminate\Routing\Router;
use Illuminate\Http\Request;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Validation\Factory;

require_once dirname(__DIR__, 2).'/TestCase.php';

class RestoreValidationFixture extends BaseModel
{
    public static $tableName = 'records';
    public static $primaryId = 'recordId';
}

class FormRestoreValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->database->getConnection()->getSchemaBuilder()->create('records', function (SchemaBlueprint $table) {
            $table->increments('recordId');
            $table->string('name');
            $table->dateTime('deleted_at')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
        });

        DB::table('records')->insert([
            ['recordId' => 1, 'name' => 'Final Exam', 'deleted_at' => null, 'deleted_by' => null],
            ['recordId' => 2, 'name' => 'Final Exam', 'deleted_at' => '2026-08-01 10:00:00', 'deleted_by' => 7],
        ]);

        config([
            'form-tool.table_meta_columns.deletedAt' => 'deleted_at',
            'form-tool.table_meta_columns.deletedBy' => 'deleted_by',
        ]);

        $this->app->instance('request', Request::create('/records/bulk-action', 'POST'));
        $this->app->instance('router', new Router($this->app['events'], $this->app));
        $validator = new Factory(new Translator(new ArrayLoader(), 'en'), $this->app);
        $validator->setPresenceVerifier(new DatabasePresenceVerifier($this->app['db']));
        $this->app->instance('validator', $validator);
    }

    public function test_restore_validation_rejects_unique_conflicts_against_active_rows(): void
    {
        $crud = Doc::create($this->resource(), new DataModel(RestoreValidationFixture::class), function (BluePrint $input) {
            $input->text('name', 'Name')->unique(function ($query) {
                $query->whereNull('deleted_at');
            })->required();
        });
        $crud->wantsArray();

        $deletedRow = DB::table('records')->where('recordId', 2)->first();

        $response = $crud->getForm()->validateRestoreData(2, $deletedRow);

        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertSame('validation.unique', $response['message']);
        $this->assertSame(['validation.unique'], $response['errors']['name']);
    }

    public function test_restore_validation_ignores_the_deleted_row_itself(): void
    {
        DB::table('records')->where('recordId', 1)->update(['name' => 'Other Exam']);

        $crud = Doc::create($this->resource(), new DataModel(RestoreValidationFixture::class), function (BluePrint $input) {
            $input->text('name', 'Name')->unique()->required();
        });
        $crud->wantsArray();

        $deletedRow = DB::table('records')->where('recordId', 2)->first();

        $this->assertTrue($crud->getForm()->validateRestoreData(2, $deletedRow));
    }

    private function resource(): object
    {
        return (object) [
            'title' => 'Records',
            'route' => 'records',
            'singularTitle' => 'Record',
        ];
    }
}
