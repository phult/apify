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
