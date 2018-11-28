<?php

namespace Megaads\Apify;

use Illuminate\Support\ServiceProvider;

class ApifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__ . '/routes.php';
        if (method_exists($this->app, 'middleware')) {
            $this->app->middleware([
                Middlewares\CorsMiddleware::class,
                Middlewares\ValidationMiddleware::class,
            ]);
        } else {
            $this->app['router']->middleware('CorsMiddleware', 'Middlewares\CorsMiddleware');
            $this->app['router']->middleware('ValidationMiddleware', 'Middlewares\ValidationMiddleware');
        }

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('Megaads\Apify\Models\BaseModel');
    }
}
