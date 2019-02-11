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
                Middlewares\AuthMiddleware::class,
            ]);
        } else {
            $kernel = $this->app->make('Illuminate\Contracts\Http\Kernel');
            $kernel->pushMiddleware('\Megaads\Apify\Middlewares\CorsMiddleware');
            $kernel->pushMiddleware('\Megaads\Apify\Middlewares\ValidationMiddleware');
            $kernel->pushMiddleware('\Megaads\Apify\Middlewares\AuthMiddleware');
        }
        if (method_exists($this->app, 'configure')) {
            $this->app->configure('apify');
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
