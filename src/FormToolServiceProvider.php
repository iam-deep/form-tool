<?php

namespace Biswadeep\FormTool;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class FormToolServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/config/form-tool.php', 'form-tool');
        $this->publishes([
            __DIR__.'/config/form-tool.php' => config_path('form-tool.php'),
            __DIR__.'/views/layouts'        => resource_path('views'.config('form-tool.adminURL').'/layouts'),
            //__DIR__.'/public/assets'        => public_path('assets/vendor/form-tool'),        // public as 3rd parameter
        ]);

        //if (!$this->app->routesAreCached())
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/views', 'form-tool');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->configureRateLimiting();
    }

    public function register()
    {
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
