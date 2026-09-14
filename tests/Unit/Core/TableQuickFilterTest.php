<?php

namespace Deep\FormTool\Tests\Unit\Core;

use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Core\Crud;
use Deep\FormTool\Core\DataModel;
use Deep\FormTool\Core\Filter;
use Deep\FormTool\Core\Form;
use Deep\FormTool\Core\Guard;
use Deep\FormTool\Core\InputTypes\BaseFilterType;
use Deep\FormTool\Core\InputTypes\Common\ICustomType;
use Deep\FormTool\Core\InputTypes\DateType;
use Deep\FormTool\Core\Table;
use Deep\FormTool\Models\BaseModel;
use Deep\FormTool\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint as Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuickFilterRecords extends BaseModel
{
    public static $tableName = 'quick_filter_records';
    public static $primaryId = 'id';
    public static $limit = 1;
}

class QuickFilterTable extends Table
{
    public function conditions(): array
    {
        return $this->setupTable();
    }
}

class QuickFilterDateInput extends DateType implements ICustomType
{
    public function getFilterHTML()
    {
        return '<input name="'.$this->getDbField().'">';
    }
}

class TableQuickFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('form-tool.callbackUrl', fn ($route, $params) => '/'.$route.($params ? '?'.http_build_query($params) : ''));
        $this->app['config']->set('form-tool.isGuarded', false);
        Guard::$instance = null;
        Guard::init(Request::create('/'));
        $this->app->instance(\Illuminate\Contracts\View\Factory::class, new class
        {
            public function make($view, $data, $mergeData = [])
            {
                return (object) $data;
            }
        });
        $this->database->schema()->create('quick_filter_records', function (Schema $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('status');
            $table->dateTime('deletedAt')->nullable();
        });
        foreach ([1, 1, 0, 2, 1, 2] as $index => $status) {
            DB::table('quick_filter_records')->insert([
                'name' => 'Record '.($index + 1),
                'status' => $status,
                'deletedAt' => $index >= 4 ? '2026-01-01 00:00:00' : null,
            ]);
        }
    }

    private function table(array $params = [], bool $softDelete = true, bool $default = true): QuickFilterTable
    {
        $this->app->instance('request', Request::create('/records', 'GET', $params));
        $table = new QuickFilterTable((object) ['route' => 'records'], new BluePrint(), new DataModel(QuickFilterRecords::class));
        $crud = $this->createMock(Crud::class);
        $crud->method('isSoftDelete')->willReturn($softDelete);
        $crud->method('getBluePrint')->willReturn(new BluePrint());
        $crud->method('getTable')->willReturn($table);
        $table->getModel()->setCrud($crud);
        $table->setCrud($crud);
        $table->create(fn ($fields) => $fields->text('name'));
        $table->quickFilter('active', 'Active', ['status' => 1], default: $default)
            ->quickFilter('inactive', 'Inactive', ['status' => 0])
            ->quickFilter('pending', 'Pending', ['status' => 2]);

        return $table;
    }

    public function test_default_filters_records_search_and_pagination_and_counts(): void
    {
        $table = $this->table();
        $where = $table->conditions();
        $page = $table->getModel()->getAll($where);
        $this->assertSame(2, $page->total());
        $this->assertCount(1, $page->items());
        $this->assertSame(1, (int) $page->items()[0]->status);
        $this->assertSame(2, $table->getModel()->search('Record', ['name'], $where)->total());
        $this->assertSame(0, $table->getModel()->search('Record 3', ['name'], $where)->total());

        $tabs = $table->getFilter()->quickFilter->quickFilters;
        $this->assertSame(['all', 'active', 'inactive', 'pending', 'trash'], array_keys($tabs));
        $this->assertSame([4, 2, 1, 1, 2], array_column($tabs, 'count'));
        $this->assertTrue($tabs['active']['active']);
        $this->assertSame('/records?quick_status=all', $tabs['all']['href']);
        $this->assertFalse($tabs['trash']['separator']);
    }

    public function test_explicit_selection_overrides_default_and_invalid_selection_uses_all(): void
    {
        foreach (['all' => 4, 'inactive' => 1, 'pending' => 1, 'trash' => 2, 'unknown' => 4] as $selection => $count) {
            $table = $this->table(['quick_status' => $selection]);
            $this->assertSame($count, $table->getModel()->getAll($table->conditions())->total(), $selection);
            $tabs = $table->getFilter()->quickFilter->quickFilters;
            $this->assertTrue($tabs[$selection === 'unknown' ? 'all' : $selection]['active']);
        }
        $table = $this->table(['quick_status' => ['active']]);
        $this->assertSame(4, $table->getModel()->getAll($table->conditions())->total());
    }

    public function test_grouped_callback_cannot_include_deleted_rows_through_or_conditions(): void
    {
        $table = $this->table();
        $table->quickFilter('open', 'Open', function ($query, $model) {
            $query->where($model->getAlias().'status', 1)->orWhere($model->getAlias().'status', 2);
        }, default: true);
        $this->assertSame(3, $table->getModel()->getAll($table->conditions())->total());
        $this->assertSame(3, $table->getFilter()->quickFilter->quickFilters['open']['count']);
    }

    public function test_filter_form_preserves_default_and_explicit_all_and_combines_conditions(): void
    {
        foreach ([[], ['quick_status' => 'all']] as $params) {
            $table = $this->table($params);
            $filter = $this->createMock(Filter::class);
            $filter->method('create')->willReturn((object) ['inputs' => []]);
            $filter->method('apply')->willReturn(fn ($query) => $query->where('id', 3));
            $table->filter = $filter;
            $selected = $params['quick_status'] ?? 'active';
            $this->assertContains('<input type="hidden" name="quick_status" value="'.$selected.'">', $table->getFilter()->filter->filterData->inputs);
            $this->assertSame($selected === 'all' ? 1 : 0, $table->getModel()->getAll($table->conditions())->total());
        }
    }

    public function test_direct_id_lookup_is_not_limited_by_default(): void
    {
        $table = $this->table(['id' => 3]);
        $page = $table->getModel()->getAll($table->conditions());
        $this->assertSame(1, $page->total());
        $this->assertSame(3, (int) $page->items()[0]->id);
        $this->assertTrue($table->getFilter()->quickFilter->quickFilters['all']['active']);
    }

    public function test_no_soft_delete_includes_matching_rows_and_hides_trash(): void
    {
        $table = $this->table([], false);
        $this->assertSame(3, $table->getModel()->getAll($table->conditions())->total());
        $this->assertArrayNotHasKey('trash', $table->getFilter()->quickFilter->quickFilters);
    }

    public function test_reserved_key_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->table()->quickFilter('trash', 'Custom', []);
    }

    public function test_all_remains_default_when_no_filter_is_marked_default(): void
    {
        $table = $this->table(default: false);
        $this->assertSame(4, $table->getModel()->getAll($table->conditions())->total());
        $tabs = $table->getFilter()->quickFilter->quickFilters;
        $this->assertTrue($tabs['all']['active']);
        $this->assertSame('/records', $tabs['all']['href']);
    }

    public function test_trash_requires_destroy_permission(): void
    {
        $enabled = new \ReflectionProperty(Guard::class, 'isEnable');
        $enabled->setValue(null, true);
        try {
            $table = $this->table(['quick_status' => 'trash']);
            $this->assertSame(4, $table->getModel()->getAll($table->conditions())->total());
            $tabs = $table->getFilter()->quickFilter->quickFilters;
            $this->assertArrayNotHasKey('trash', $tabs);
            $this->assertTrue($tabs['all']['active']);
        } finally {
            $enabled->setValue(null, false);
        }
    }

    private function regularFilter(QuickFilterTable $table, string $column = 'status'): void
    {
        $bluePrint = $table->getBluePrint();
        $form = $this->createMock(Form::class);
        $form->method('getRoute')->willReturn('records');
        $form->method('getModel')->willReturn($table->getModel());
        $bluePrint->setForm($form);
        $input = new BaseFilterType();
        $input->init($bluePrint, $column);
        $table->filter([$column => $input]);
    }

    public function test_filtered_tab_shows_total_for_zero_value_and_keeps_filters_in_link(): void
    {
        $table = $this->table(['quick_status' => 'all', 'status' => '0', 'page' => 3, 'per_page' => 20]);
        $this->regularFilter($table);
        $tabs = $table->getFilter()->quickFilter->quickFilters;
        $this->assertSame(['all', 'active', 'inactive', 'pending', 'trash', 'filtered'], array_keys($tabs));
        $this->assertSame(1, $tabs['filtered']['count']);
        $this->assertTrue($tabs['filtered']['active']);
        $this->assertFalse($tabs['all']['active']);
        $this->assertTrue($tabs['trash']['separator']);
        $this->assertFalse($tabs['filtered']['separator']);
        $this->assertSame('/records?quick_status=all&status=0&per_page=20', $tabs['filtered']['href']);
    }

    public function test_filtered_count_uses_status_scope_and_shows_zero_matches(): void
    {
        foreach ([['quick_status' => 'all'], ['quick_status' => 'trash'], []] as $params) {
            $table = $this->table($params + ['status' => 0]);
            $this->regularFilter($table);
            $tabs = $table->getFilter()->quickFilter->quickFilters;
            $this->assertSame(($params['quick_status'] ?? '') === 'all' ? 1 : 0, $tabs['filtered']['count']);
        }
        $table = $this->table(['status' => 1, 'search' => 'Record 1', 'page' => 2]);
        $this->regularFilter($table);
        $tabs = $table->getFilter()->quickFilter->quickFilters;
        $this->assertSame(2, $tabs['filtered']['count']);
        $this->assertSame('/records?quick_status=active&status=1', $tabs['filtered']['href']);
    }

    public function test_empty_filters_and_navigation_do_not_show_filtered_tab(): void
    {
        foreach ([null, '', []] as $empty) {
            $table = $this->table(['status' => $empty, 'page' => 2, 'per_page' => 50, 'orderby' => 'name', 'search' => 'Record', 'unknown' => 'value']);
            $this->regularFilter($table);
            $this->assertArrayNotHasKey('filtered', $table->getFilter()->quickFilter->quickFilters);
        }
        $table = $this->table(['id' => 3, 'status' => 1]);
        $this->regularFilter($table);
        $this->assertArrayNotHasKey('filtered', $table->getFilter()->quickFilter->quickFilters);
    }

    public function test_range_filter_count_does_not_change_subsequent_list_conditions(): void
    {
        $table = $this->table(['quick_status' => 'trash', 'deletedAtFrom' => '01-01-2026']);
        $this->regularFilter($table);
        $table->getBluePrint()->custom(QuickFilterDateInput::class, 'deletedAt');
        $table->filter(['quick_filter_records.deletedAt' => 'range']);
        $tabs = $table->getFilter()->quickFilter->quickFilters;
        $this->assertSame(2, $tabs['filtered']['count']);
        $this->assertSame(2, $table->getModel()->getAll($table->conditions())->total());
        $this->assertSame(2, $table->getFilter()->quickFilter->quickFilters['filtered']['count']);
    }
}
