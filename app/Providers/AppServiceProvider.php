<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PesapalAuthService;
use App\Services\PesapalService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->singleton(PesapalAuthService::class, function ($app) {
            return new PesapalAuthService();
        });
    
        $this->app->singleton(PesapalService::class, function ($app) {
            return new PesapalService($app->make(PesapalAuthService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
