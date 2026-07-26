<?php

namespace Deep\FormTool\Tests\Unit\Core;

use Deep\FormTool\Core\Auth;
use Deep\FormTool\Core\DataModel;
use Deep\FormTool\Models\BaseModel;
use Deep\FormTool\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use ReflectionMethod;

class DataModelSoftDeleteFixture extends BaseModel
{
    public static $tableName = 'records';
    public static $primaryId = 'recordId';
    public static $token = 'recordToken';
}

class DataModelAuthUser extends Model
{
    public static function user()
    {
        return (object) ['id' => 9];
    }
}

class DataModelSoftDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->database->getConnection()->getSchemaBuilder()->create('records', function (Blueprint $table) {
            $table->increments('recordId');
            $table->string('recordToken')->unique();
            $table->string('name');
            $table->unsignedInteger('deleted_by')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });

        DB::table('records')->insert([
            ['recordId' => 1, 'recordToken' => 'active-a', 'name' => 'Active A', 'deleted_by' => null, 'deleted_at' => null],
            ['recordId' => 2, 'recordToken' => 'active-b', 'name' => 'Active B', 'deleted_by' => null, 'deleted_at' => null],
            ['recordId' => 3, 'recordToken' => 'deleted-a', 'name' => 'Deleted A', 'deleted_by' => 7, 'deleted_at' => '2026-07-19 10:00:00'],
            ['recordId' => 4, 'recordToken' => 'deleted-b', 'name' => 'Deleted B', 'deleted_by' => 7, 'deleted_at' => '2026-07-19 10:00:00'],
        ]);

        config([
            'form-tool.table_meta_columns.deletedBy' => 'deleted_by',
            'form-tool.table_meta_columns.deletedAt' => 'deleted_at',
            'form-tool.auth' => [
                'isCustomAuth' => true,
                'userModel' => DataModelAuthUser::class,
            ],
        ]);

        $this->resetFormToolAuth();
    }

    protected function tearDown(): void
    {
        $this->resetFormToolAuth();

        parent::tearDown();
    }

    public function test_update_delete_sets_configured_metadata_for_multiple_ids(): void
    {
        $model = new DataModel(DataModelSoftDeleteFixture::class);

        $this->assertSame(2, $model->updateDelete([1, 2]));

        $rows = DB::table('records')->whereIn('recordId', [1, 2])->get();
        foreach ($rows as $row) {
            $this->assertSame(9, $row->deleted_by);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $row->deleted_at);
        }

        $this->assertStringContainsString(
            '@deprecated',
            (new ReflectionMethod(DataModel::class, 'updateDelete'))->getDocComment()
        );
    }

    public function test_update_delete_supports_token_arrays(): void
    {
        $model = new DataModel(DataModelSoftDeleteFixture::class);

        $this->assertSame(2, $model->updateDelete(['active-a', 'active-b'], true));
        $this->assertSame(2, DB::table('records')->whereNotNull('deleted_at')->whereIn('recordId', [1, 2])->count());
    }

    public function test_restore_clears_configured_metadata_for_multiple_ids(): void
    {
        $model = new DataModel(DataModelSoftDeleteFixture::class);

        $this->assertSame(2, $model->restore([3, 4]));
        $this->assertSame(0, DB::table('records')->whereNotNull('deleted_at')->count());
        $this->assertSame(0, DB::table('records')->whereNotNull('deleted_by')->count());
    }

    public function test_update_delete_remains_as_a_deprecated_compatibility_wrapper(): void
    {
        $model = new DataModel(DataModelSoftDeleteFixture::class);

        $this->assertSame(1, $model->updateDelete(1));
        $this->assertNotNull(DB::table('records')->where('recordId', 1)->value('deleted_at'));
    }

    public function test_soft_delete_boolean_remains_the_configuration_switch(): void
    {
        $model = new DataModel(DataModelSoftDeleteFixture::class);
        $model->softDelete(false);

        $property = (new ReflectionClass(DataModel::class))->getProperty('isSoftDelete');
        $property->setAccessible(true);

        $this->assertFalse($property->getValue($model));
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
