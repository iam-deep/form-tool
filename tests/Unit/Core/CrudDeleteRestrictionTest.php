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

require_once dirname(__DIR__, 2).'/TestCase.php';

class CrudDeleteRestrictionFixture extends BaseModel
{
    public static $tableName = 'subjects';
    public static $primaryId = 'subjectId';
}

class CrudDeleteRestrictionSectionFixture extends BaseModel
{
    public static $tableName = 'sections';
    public static $primaryId = 'sectionId';
}

class CrudDeleteRestrictionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->database->getConnection()->getSchemaBuilder()->create('cruds', function (SchemaBlueprint $table) {
            $table->id();
            $table->string('route');
            $table->text('data');
            $table->string('classPath')->nullable();
        });
        $this->database->getConnection()->getSchemaBuilder()->create('subjects', function (SchemaBlueprint $table) {
            $table->increments('subjectId');
            $table->unsignedInteger('classId');
        });
        $this->database->getConnection()->getSchemaBuilder()->create('sections', function (SchemaBlueprint $table) {
            $table->increments('sectionId');
            $table->string('section');
        });

        $this->app->instance('request', Request::create('/subjects', 'GET'));
        $this->app->instance('router', new Router($this->app['events'], $this->app));
    }

    public function test_do_not_save_selects_are_omitted_from_generated_delete_restrictions(): void
    {
        $crud = Doc::create($this->resource(), new DataModel(CrudDeleteRestrictionFixture::class), function (BluePrint $input) {
            $input->select('classId', 'Class')->options('classes.classId.class');
            $input->select('sectionIds', 'Sections')->options('sections.sectionId.section')->multiple();
        });

        $crud->doNotSave('sectionIds')->saveCrud();

        $data = json_decode(DB::table('cruds')->where('route', 'subjects')->value('data'));

        $columns = array_column($data->foreignKey, 'column');

        $this->assertContains('classId', $columns);
        $this->assertNotContains('sectionIds', $columns);
    }

    public function test_explicit_restrictions_remain_available_for_do_not_save_fields(): void
    {
        $crud = Doc::create($this->resource(), new DataModel(CrudDeleteRestrictionFixture::class), function (BluePrint $input) {
            $input->select('sectionIds', 'Sections')->options('sections.sectionId.section')->multiple();
        });

        $crud->doNotSave('sectionIds')
            ->deleteRestrictForOthers('subject_sections', 'sectionIds', 'Sections')
            ->saveCrud();

        $data = json_decode(DB::table('cruds')->where('route', 'subjects')->value('data'));

        $explicitRestriction = collect($data->foreignKey)->firstWhere('table', 'subject_sections');

        $this->assertNotNull($explicitRestriction);
        $this->assertSame('sectionIds', $explicitRestriction->column);
    }

    public function test_stale_delete_restriction_metadata_for_a_missing_column_is_ignored(): void
    {
        DB::table('cruds')->insert([
            'route' => 'subjects',
            'data' => json_encode([
                'foreignKey' => [[
                    'table' => 'sections',
                    'column' => 'sectionIds',
                    'label' => 'Sections',
                ]],
                'foreignModules' => [],
                'main' => [
                    'title' => 'Subjects',
                    'table' => 'subjects',
                    'id' => 'subjectId',
                ],
            ]),
            'classPath' => null,
        ]);

        $crud = Doc::create(
            $this->resource('Sections', 'sections', 'Section'),
            new DataModel(CrudDeleteRestrictionSectionFixture::class),
            fn (BluePrint $input) => $input->text('section', 'Section')
        );
        $method = new \ReflectionMethod($crud->getForm(), 'checkForeignKeyRestriction');

        $this->assertTrue($method->invoke($crud->getForm(), 1766, (object) ['sectionId' => 1766]));
    }

    private function resource(
        string $title = 'Subjects',
        string $route = 'subjects',
        string $singularTitle = 'Subject'
    ): object {
        return (object) [
            'title' => $title,
            'route' => $route,
            'singularTitle' => $singularTitle,
        ];
    }
}
