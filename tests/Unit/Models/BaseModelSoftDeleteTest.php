<?php

namespace Deep\FormTool\Tests\Unit\Models;

use Deep\FormTool\Models\BaseModel;
use Deep\FormTool\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class BaseModelSoftDeleteFixture extends BaseModel
{
    public static $tableName = 'records';
    public static $primaryId = 'recordId';
    public static $token = 'recordToken';
}

class BaseModelSoftDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->database->getConnection()->getSchemaBuilder()->create('records', function (Blueprint $table) {
            $table->increments('recordId');
            $table->string('recordToken')->unique();
            $table->string('name');
            $table->unsignedInteger('deletedBy')->nullable();
            $table->dateTime('deletedAt')->nullable();
        });

        DB::table('records')->insert([
            ['recordId' => 1, 'recordToken' => 'active-a', 'name' => 'Active A', 'deletedBy' => null, 'deletedAt' => null],
            ['recordId' => 2, 'recordToken' => 'active-b', 'name' => 'Active B', 'deletedBy' => null, 'deletedAt' => null],
            ['recordId' => 3, 'recordToken' => 'deleted-a', 'name' => 'Deleted A', 'deletedBy' => 7, 'deletedAt' => '2026-07-19 10:00:00'],
            ['recordId' => 4, 'recordToken' => 'deleted-b', 'name' => 'Deleted B', 'deletedBy' => 7, 'deletedAt' => '2026-07-19 10:00:00'],
        ]);

        BaseModelSoftDeleteFixture::setSoftDelete(true);
    }

    protected function tearDown(): void
    {
        BaseModelSoftDeleteFixture::setSoftDelete(true);

        parent::tearDown();
    }

    public function test_update_one_updates_only_active_rows_when_soft_delete_is_enabled(): void
    {
        $this->assertSame(1, BaseModelSoftDeleteFixture::updateOne(1, ['name' => 'Updated active']));
        $this->assertSame(0, BaseModelSoftDeleteFixture::updateOne(3, ['name' => 'Updated deleted']));

        $this->assertSame('Updated active', DB::table('records')->where('recordId', 1)->value('name'));
        $this->assertSame('Deleted A', DB::table('records')->where('recordId', 3)->value('name'));
    }

    public function test_update_one_can_update_deleted_rows_when_soft_delete_is_disabled(): void
    {
        BaseModelSoftDeleteFixture::setSoftDelete(false);

        $this->assertSame(1, BaseModelSoftDeleteFixture::updateOne(3, ['name' => 'Updated deleted']));
        $this->assertSame('Updated deleted', DB::table('records')->where('recordId', 3)->value('name'));
    }

    public function test_soft_delete_accepts_one_id_and_only_changes_an_active_row(): void
    {
        $data = ['deletedBy' => 9, 'deletedAt' => '2026-07-20 10:00:00'];

        $this->assertSame(1, BaseModelSoftDeleteFixture::softDelete(1, $data));
        $this->assertSame(0, BaseModelSoftDeleteFixture::softDelete(3, $data));
        $this->assertSame(9, DB::table('records')->where('recordId', 1)->value('deletedBy'));
    }

    public function test_soft_delete_accepts_multiple_tokens_in_one_operation(): void
    {
        $data = ['deletedBy' => 9, 'deletedAt' => '2026-07-20 10:00:00'];

        $this->assertSame(2, BaseModelSoftDeleteFixture::softDelete(['active-a', 'active-b'], $data, true));
        $this->assertSame(2, DB::table('records')->whereNotNull('deletedAt')->whereIn('recordId', [1, 2])->count());
    }

    public function test_restore_accepts_one_id_and_only_changes_a_deleted_row(): void
    {
        $data = ['deletedBy' => null, 'deletedAt' => null];

        $this->assertSame(1, BaseModelSoftDeleteFixture::restore(3, $data));
        $this->assertSame(0, BaseModelSoftDeleteFixture::restore(1, $data));
        $this->assertNull(DB::table('records')->where('recordId', 3)->value('deletedAt'));
    }

    public function test_restore_accepts_multiple_ids_in_one_operation(): void
    {
        $data = ['deletedBy' => null, 'deletedAt' => null];

        $this->assertSame(2, BaseModelSoftDeleteFixture::restore([3, 4], $data));
        $this->assertSame(0, DB::table('records')->whereNotNull('deletedAt')->count());
    }

    public function test_empty_transition_arrays_do_not_change_rows(): void
    {
        $before = DB::table('records')->orderBy('recordId')->get()->toArray();

        $this->assertSame(0, BaseModelSoftDeleteFixture::softDelete([], ['deletedAt' => '2026-07-20 10:00:00']));
        $this->assertSame(0, BaseModelSoftDeleteFixture::restore([], ['deletedAt' => null]));
        $this->assertEquals($before, DB::table('records')->orderBy('recordId')->get()->toArray());
    }
}
