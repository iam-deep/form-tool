<?php

namespace Biswadeep\FormTool;

use Illuminate\Support\ServiceProvider;

class FormToolServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/views', 'FormTool');
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        $this->mergeConfigFrom(__DIR__ . '/config/form-tool.php', 'form-tool');
        $this->publishes([
            __DIR__ . '/config/form-tool.php' => config_path('form-tool.php')
        ]);
    }

    public function register()
    {

    }
}