<?php

namespace Deep\FormTool\Tests\Unit\Core;

use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Core\DataModel;
use Deep\FormTool\Core\Doc;
use Deep\FormTool\Models\BaseModel;
use Deep\FormTool\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint as SchemaBlueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
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
            $table->date('date')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
        });

        DB::table('records')->insert([
            ['recordId' => 1, 'name' => 'Final Exam', 'date' => '2026-08-01', 'deleted_at' => null, 'deleted_by' => null],
            ['recordId' => 2, 'name' => 'Final Exam', 'date' => '2026-08-02', 'deleted_at' => '2026-08-01 10:00:00', 'deleted_by' => 7],
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

    public function test_restore_skips_other_rules_and_callbacks_but_create_still_validates(): void
    {
        $crud = Doc::create($this->resource(), new DataModel(RestoreValidationFixture::class), function (BluePrint $input) {
            $input->date('date', 'Date')->required()->validations(['after:2099-01-01']);
            $input->text('mobile', 'Mobile')->required()->validations([function () {
                $this->fail('Custom field rules must not run during restore.');
            }]);
            $input->email('email', 'Email')->required();
        })->callbackValidation(function () {
            $this->fail('Custom form validation must not run during restore.');
        });
        $crud->wantsArray();

        $deletedRow = DB::table('records')->where('recordId', 2)->first();

        $this->assertTrue($crud->getForm()->validateRestoreData(2, $deletedRow));
        $this->assertFalse($crud->getForm()->isRestoreValidation());
        $response = $crud->getForm()->validateDuplicateData(['date' => 'invalid']);
        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
    }

    public function test_restore_checks_slug_conflicts_and_ignores_other_trashed_rows(): void
    {
        DB::table('records')->update(['name' => 'final-exam']);
        $crud = Doc::create($this->resource(), new DataModel(RestoreValidationFixture::class), function (BluePrint $input) {
            $input->text('name', 'Name')->slug();
        });
        $crud->wantsArray();
        $deletedRow = DB::table('records')->where('recordId', 2)->first();
        $response = $crud->getForm()->validateRestoreData(2, $deletedRow);
        $this->assertFalse($response['success']);

        DB::table('records')->where('recordId', 1)->update(['deleted_at' => '2026-08-01 10:00:00']);
        $this->assertTrue($crud->getForm()->validateRestoreData(2, $deletedRow));
    }

    public function test_restore_checks_explicit_unique_rules_without_mutating_the_original_rule(): void
    {
        foreach (['unique:records,name', \Illuminate\Validation\Rule::unique('records', 'name')] as $rule) {
            DB::table('records')->where('recordId', 1)->update(['deleted_at' => null]);
            $before = (string) $rule;
            $crud = Doc::create($this->resource(), new DataModel(RestoreValidationFixture::class), function (BluePrint $input) use ($rule) {
                $input->text('name', 'Name')->validations([$rule]);
            });
            $crud->wantsArray();
            $deletedRow = DB::table('records')->where('recordId', 2)->first();
            $response = $crud->getForm()->validateRestoreData(2, $deletedRow);
            $this->assertFalse($response['success']);
            DB::table('records')->where('recordId', 1)->update(['deleted_at' => '2026-08-01 10:00:00']);
            $this->assertTrue($crud->getForm()->validateRestoreData(2, $deletedRow));
            $this->assertSame($before, (string) $rule);
        }
    }

    public function test_restore_checks_unique_combinations_using_stored_values_and_only_active_rows(): void
    {
        DB::table('records')->where('recordId', 1)->update(['date' => '2026-08-02']);
        $crud = Doc::create($this->resource(), new DataModel(RestoreValidationFixture::class), function (BluePrint $input) {
            $input->text('name', 'Name');
            $input->date('date', 'Date');
        })->unique(['name', 'date']);
        $crud->wantsArray();
        $deletedRow = DB::table('records')->where('recordId', 2)->first();
        $response = $crud->getForm()->validateRestoreData(2, $deletedRow);
        $this->assertFalse($response['status']);
        $this->assertStringContainsString('combination', $response['message']);
        DB::table('records')->where('recordId', 1)->update(['deleted_at' => '2026-08-01 10:00:00']);
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
