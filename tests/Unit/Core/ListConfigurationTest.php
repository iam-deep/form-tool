<?php

namespace Deep\FormTool\Tests\Unit\Core;

use Deep\FormTool\Core\DataModel;
use Deep\FormTool\Core\ListConfiguration;
use Deep\FormTool\Core\Table;
use Deep\FormTool\Core\TableField;
use Deep\FormTool\Models\BaseModel;
use Deep\FormTool\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Factory as ValidationFactory;

class ListConfigurationModel extends BaseModel
{
    public static $tableName = 'list_records';
    public static $primaryId = 'recordId';
    public static $limit = 3;
}

class ListConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance('validator', new ValidationFactory(new Translator(new ArrayLoader(), 'en'), $this->app));
        $this->app->instance('request', Request::create('/'));

        $this->database->getConnection()->getSchemaBuilder()->create('list_records', function (Blueprint $table) {
            $table->increments('recordId');
            $table->string('name');
            $table->string('email');
            $table->dateTime('deletedAt')->nullable();
        });

        for ($i = 1; $i <= 6; $i++) {
            DB::table('list_records')->insert([
                'name' => 'Name '.$i,
                'email' => 'user'.$i.'@example.com',
            ]);
        }
    }

    public function test_it_uses_saved_school_configuration_and_request_page_limit(): void
    {
        $configuration = new ListConfiguration([
            'columns' => ['name' => 'Name', 'email' => 'Email'],
            'filters' => ['name' => 'Name', 'email' => 'Email'],
            'defaults' => ['columns' => ['name'], 'filters' => ['name'], 'perPage' => 20],
            'values' => ['columns' => ['email'], 'filters' => [], 'perPage' => 50],
            'perPageOptions' => [20, 50, 100],
            'saveUrl' => '/list-settings',
            'canUpdate' => true,
        ]);

        $this->assertSame(['email'], $configuration->selectedColumns());
        $this->assertSame([], $configuration->selectedFilters());
        $this->assertSame(50, $configuration->defaultPerPage());
        $this->assertSame(100, $configuration->perPage(Request::create('/students', 'GET', ['per_page' => 100])));
        $this->assertSame(50, $configuration->defaultPerPage());
        $this->assertSame(50, $configuration->perPage(Request::create('/students', 'GET', ['per_page' => 999])));
        $this->assertTrue($configuration->canUpdate());
    }

    public function test_it_rejects_unknown_columns_filters_and_page_limits(): void
    {
        $configuration = new ListConfiguration([
            'columns' => ['name' => 'Name'],
            'filters' => ['email' => 'Email'],
            'perPageOptions' => [20, 50],
        ]);

        $this->expectException(ValidationException::class);

        $configuration->validate([
            'columns' => ['unknown'],
            'filters' => ['unknown'],
            'perPage' => 5000,
        ]);
    }

    public function test_table_fields_keep_fixed_cells_and_hide_unselected_configurable_columns(): void
    {
        $table = new Table((object) [], new \Deep\FormTool\Core\BluePrint(), new DataModel(ListConfigurationModel::class));
        $fields = new TableField($table);
        $fields->slNo();
        $fields->text('name', 'Name');
        $fields->text('email', 'Email');

        $fields->showConfiguredColumns(
            ['name' => 'Name', 'email' => 'Email'],
            ['email']
        );

        $this->assertSame(['#', 'email'], $fields->toArray());
    }

    public function test_data_model_applies_and_restores_the_configured_page_limit(): void
    {
        DB::table('list_records')->delete();
        Paginator::currentPageResolver(fn () => 1);
        Paginator::currentPathResolver(fn () => '/');

        $model = new DataModel(ListConfigurationModel::class);
        $result = $model->perPage(2)->getAll();

        $this->assertSame(2, $result->perPage());
        $this->assertCount(0, $result->items());
        $this->assertSame(3, ListConfigurationModel::$limit);
    }

    public function test_table_uses_global_page_options_without_module_configuration(): void
    {
        config()->set('form-tool.list.perPageOptions', [2, 4]);
        config()->set('form-tool.list.defaultPerPage', 2);
        $this->app->instance('request', Request::create('/students', 'GET', ['per_page' => 4]));

        $model = new DataModel(ListConfigurationModel::class);
        $table = new Table((object) [], new \Deep\FormTool\Core\BluePrint(), $model);
        $configuration = new \ReflectionProperty($table, 'listConfiguration');
        $perPage = new \ReflectionProperty($model, 'perPage');

        $this->assertTrue($configuration->getValue($table)->perPageEnabled());
        $this->assertSame([2, 4], $configuration->getValue($table)->perPageOptions());
        $this->assertSame(4, $perPage->getValue($model));
    }

    public function test_module_can_disable_the_global_page_selector(): void
    {
        config()->set('form-tool.list.perPageEnabled', true);
        $this->app->instance('request', Request::create('/students', 'GET', ['per_page' => 50]));

        $model = new DataModel(ListConfigurationModel::class);
        $table = new Table((object) [], new \Deep\FormTool\Core\BluePrint(), $model);
        $table->configurable(['perPageEnabled' => false]);
        $perPage = new \ReflectionProperty($model, 'perPage');

        $this->assertSame('', $table->getListConfiguration('perPage'));
        $this->assertNull($perPage->getValue($model));
    }

    public function test_it_hides_list_settings_when_configuration_is_read_only(): void
    {
        $configuration = new ListConfiguration([
            'columns' => ['name' => 'Name'],
            'filters' => ['name' => 'Name'],
            'perPageOptions' => [20, 50],
            'canUpdate' => false,
        ]);
        $table = new Table((object) [], new \Deep\FormTool\Core\BluePrint(), new DataModel(ListConfigurationModel::class));
        $property = new \ReflectionProperty($table, 'listConfiguration');
        $property->setValue($table, $configuration);

        $this->assertSame('', $table->getListConfiguration('settings'));
    }
}
