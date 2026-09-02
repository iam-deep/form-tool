<?php

namespace Deep\FormTool\Tests\Unit\Core;

require_once dirname(__DIR__, 2).'/TestCase.php';

use Deep\FormTool\Core\ActionLogger;
use Deep\FormTool\Dtos\ActionLoggerDto;
use Deep\FormTool\Enums\ActionLoggerEnum;
use Deep\FormTool\Exceptions\FormToolException;
use Deep\FormTool\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActionLoggerContextResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->database->getConnection()->getSchemaBuilder()->create('action_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('organization_id')->nullable();
            $table->string('action');
            $table->string('module')->nullable();
            $table->string('route')->nullable();
            $table->string('path')->nullable();
            $table->string('refId')->nullable();
            $table->string('token')->nullable();
            $table->string('description')->nullable();
            $table->mediumText('data')->nullable();
            $table->text('extraData')->nullable();
            $table->string('ipAddress')->nullable();
            $table->string('userAgent')->nullable();
            $table->unsignedInteger('createdBy')->nullable();
            $table->string('createdByName')->nullable();
            $table->dateTime('createdAt')->nullable();
        });

        $this->app->instance('request', Request::create('/records', 'POST'));
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
    }

    public function test_resolver_context_is_added_without_overriding_form_tool_fields(): void
    {
        config(['form-tool.actionLogContextResolver' => fn () => [
            'organization_id' => 17,
            'action' => 'tampered',
        ]]);

        ActionLogger::log($this->action(ActionLoggerEnum::CREATE));

        $row = DB::table('action_logs')->sole();

        $this->assertSame(17, $row->organization_id);
        $this->assertSame('create', $row->action);
    }

    public function test_resolver_context_is_added_to_every_bulk_row(): void
    {
        config(['form-tool.actionLogContextResolver' => fn () => ['organization_id' => 23]]);

        ActionLogger::log([
            $this->action(ActionLoggerEnum::CREATE),
            $this->action(ActionLoggerEnum::UPDATE),
        ]);

        $this->assertSame([23, 23], DB::table('action_logs')->pluck('organization_id')->all());
    }

    public function test_no_resolver_preserves_existing_behavior(): void
    {
        config(['form-tool.actionLogContextResolver' => null]);

        ActionLogger::log($this->action(ActionLoggerEnum::CREATE));

        $this->assertNull(DB::table('action_logs')->value('organization_id'));
    }

    public function test_configured_resolver_must_be_callable(): void
    {
        config(['form-tool.actionLogContextResolver' => 'not-callable']);

        $this->expectException(FormToolException::class);

        ActionLogger::log($this->action(ActionLoggerEnum::CREATE));
    }

    public function test_configured_resolver_must_return_an_array(): void
    {
        config(['form-tool.actionLogContextResolver' => fn () => 17]);

        $this->expectException(FormToolException::class);

        ActionLogger::log($this->action(ActionLoggerEnum::CREATE));
    }

    private function action(ActionLoggerEnum $action): ActionLoggerDto
    {
        return new ActionLoggerDto(
            action: $action,
            moduleTitle: 'Records',
            route: 'records',
            id: '11',
        );
    }
}
