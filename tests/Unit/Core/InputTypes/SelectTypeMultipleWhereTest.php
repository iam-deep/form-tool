<?php

namespace Deep\FormTool\Tests\Unit\Core\InputTypes;

use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Core\DataModel;
use Deep\FormTool\Core\Form;
use Deep\FormTool\Core\InputTypes\SelectType;
use Deep\FormTool\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint as SchemaBlueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelectTypeMultipleWhereTest extends TestCase
{
    public function test_database_where_condition_is_reused_for_every_multiple_row(): void
    {
        $this->database->getConnection()->getSchemaBuilder()->create('subjects', function (SchemaBlueprint $table) {
            $table->increments('subjectId');
            $table->unsignedInteger('classId');
            $table->string('subject');
            $table->unsignedInteger('isOptional');
            $table->dateTime('deletedAt')->nullable();
        });

        DB::table('subjects')->insert([
            ['subjectId' => 1, 'classId' => 10, 'subject' => 'Optional Subject', 'isOptional' => 1],
            ['subjectId' => 2, 'classId' => 10, 'subject' => 'Compulsory Subject', 'isOptional' => 0],
            ['subjectId' => 3, 'classId' => 20, 'subject' => 'Other Class Subject', 'isOptional' => 1],
        ]);

        $root = new BluePrint();
        $this->app->instance('request', Request::create('/records/1/edit', 'GET'));
        $root->setForm(new Form((object) ['route' => 'records'], $root, new DataModel()));
        $root->select('classId')->setValue(10);

        $subjectSelect = null;
        $root->multiple('rows', 'Rows', function (BluePrint $row) use ($root, &$subjectSelect) {
            $subjectSelect = $row->select('subjectId', 'Subject')
                ->options('subjects.subjectId.subject.subjectId.asc', function ($query) {
                    $query->where('isOptional', 1);
                })
                ->depend('classId', 'classId', $root)
                ->multiple();
        });

        $this->assertInstanceOf(SelectType::class, $subjectSelect);

        $firstRowOptions = $subjectSelect->getOptions([]);
        $secondRowOptions = $subjectSelect->getOptions([]);

        foreach ([$firstRowOptions, $secondRowOptions] as $options) {
            $this->assertStringContainsString('value="1"', $options);
            $this->assertStringNotContainsString('value="2"', $options);
            $this->assertStringNotContainsString('value="3"', $options);
        }
    }
}
