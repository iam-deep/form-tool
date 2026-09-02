<?php

namespace Deep\FormTool\Tests\Unit\Core;

use Deep\FormTool\Core\Guard;
use Deep\FormTool\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Mockery;
use ReflectionClass;

class GuardRequestStateTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflection = new ReflectionClass(Guard::class);
        $reflection->setStaticPropertyValue('instance', null);
        $reflection->setStaticPropertyValue('isEnable', false);

        parent::tearDown();
    }

    public function test_a_new_request_does_not_inherit_the_previous_route_action(): void
    {
        config(['form-tool.isGuarded' => true]);
        $router = Mockery::mock();
        $router->shouldReceive('currentRouteName')->once()->andReturn('users.update');
        $router->shouldReceive('currentRouteName')->once()->andReturn('dashboard');
        Route::swap($router);

        $permissions = json_encode([
            'users' => ['view' => 1, 'edit' => 1],
            'dashboard' => ['view' => 1],
        ], JSON_THROW_ON_ERROR);

        Guard::init(Request::create('/users/1', 'PUT'), permissions: $permissions);
        Guard::init(Request::create('/dashboard'), permissions: $permissions);

        $this->assertTrue(Guard::hasView());
    }
}
