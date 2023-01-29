<?php

namespace Deep\FormTool;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class FormToolServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/config/form-tool.php', 'form-tool');

        //if (!$this->app->routesAreCached())
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/views', 'form-tool');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->publishes([__DIR__.'/config/form-tool.php' => config_path('form-tool.php')], 'config');
        $this->publishes([
            __DIR__.'/views' => resource_path('views/vendor/form-tool/'),
        ], 'views');

        $this->configureRateLimiting();
    }

    protected function configureRateLimiting()
    {
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(15)->response(function () {
                    return response('You have reached login limit! Please try after sometime.', 429);
                }),
                Limit::perMinute(10)->by($request->email.$request->ip())->response(function () {
                    return response('You have reached login limit! Please try after sometime.', 429);
                }),
            ];
        });
    }
}
