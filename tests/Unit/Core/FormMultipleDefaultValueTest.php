<?php

namespace Deep\FormTool\Tests\Unit\Core;

use Deep\FormTool\Core\Auth;
use Deep\FormTool\Core\BluePrint;
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

class FormMultipleDefaultValueFixture extends BaseModel
{
    public static $tableName = 'records';
    public static $primaryId = 'recordId';
}

class FormMultipleDefaultValueAuthUser extends Model
{
    public static function user(): object
    {
        return (object) ['id' => 7];
    }
}

class FormMultipleDefaultValueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $schema = $this->database->getConnection()->getSchemaBuilder();
        $schema->create('records', function (SchemaBlueprint $table) {
            $table->increments('recordId');
            $table->unsignedInteger('createdBy')->nullable();
            $table->dateTime('createdAt')->nullable();
            $table->unsignedInteger('updatedBy')->nullable();
            $table->dateTime('updatedAt')->nullable();
        });
        $schema->create('record_parts', function (SchemaBlueprint $table) {
            $table->increments('partId');
            $table->unsignedInteger('recordId');
            $table->unsignedInteger('enabled')->nullable();
        });

        config(['form-tool.auth' => [
            'isCustomAuth' => true,
            'userModel' => FormMultipleDefaultValueAuthUser::class,
        ]]);

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

    public function test_create_preserves_an_explicit_zero_instead_of_replacing_it_with_the_default(): void
    {
        $crud = $this->makeCrud(Request::create('/records', 'POST', [
            'method' => 'CREATE',
            'redirect' => '/records',
            'parts' => [['enabled' => '0']],
        ]));

        $response = $crud->store();

        $this->assertTrue($response['status']);
        $this->assertSame(0, DB::table('record_parts')->value('enabled'));
    }

    public function test_update_preserves_an_explicit_zero_instead_of_replacing_it_with_the_default(): void
    {
        DB::table('records')->insert(['recordId' => 1]);
        DB::table('record_parts')->insert(['recordId' => 1, 'enabled' => 1]);

        $crud = $this->makeCrud(Request::create('/records/1', 'PUT', [
            'parts' => [['enabled' => '0']],
        ]));

        $response = $crud->update(1);

        $this->assertTrue($response['status']);
        $this->assertSame(0, DB::table('record_parts')->where('recordId', 1)->value('enabled'));
    }

    public function test_create_uses_the_default_when_the_multiple_value_is_null(): void
    {
        $crud = $this->makeCrud(Request::create('/records', 'POST', [
            'method' => 'CREATE',
            'redirect' => '/records',
            'parts' => [['enabled' => null]],
        ]));

        $response = $crud->store();

        $this->assertTrue($response['status']);
        $this->assertSame(1, DB::table('record_parts')->value('enabled'));
    }

    private function makeCrud(Request $request)
    {
        $this->app->instance('request', $request);
        Doc::setState($request->isMethod('PUT') ? CrudState::UPDATE : CrudState::STORE);

        $crud = Doc::create($this->resource(), new DataModel(FormMultipleDefaultValueFixture::class), function (BluePrint $input) {
            $input->multiple('parts', 'Parts', function (BluePrint $part) {
                $part->select('enabled', 'Enabled')
                    ->options([1 => 'Yes', 0 => 'No'])
                    ->noFirst()
                    ->default(1);
            })->table('record_parts', 'partId', 'recordId', '');
        });
        $crud->softDelete(false)->wantsArray();
        $crud->actionLog(false);

        return $crud;
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
        $reflection = new ReflectionClass(Auth::class);
        foreach (['config', 'user'] as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue(null, null);
        }
    }
}
