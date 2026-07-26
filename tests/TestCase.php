<?php

namespace Deep\FormTool\Tests;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Container $app;
    protected Capsule $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Container();
        Container::setInstance($this->app);

        $config = require dirname(__DIR__).'/src/config/form-tool.php';
        $this->app->instance('config', new Repository(['form-tool' => $config]));

        $this->database = new Capsule($this->app);
        $this->database->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $this->database->setEventDispatcher(new Dispatcher($this->app));
        $this->database->setAsGlobal();
        $this->database->bootEloquent();

        $this->app->instance('db', $this->database->getDatabaseManager());
        Facade::setFacadeApplication($this->app);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(new Container());

        parent::tearDown();
    }
}
