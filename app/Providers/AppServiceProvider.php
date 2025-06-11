<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
{
    $this->app->singleton(\App\Services\FirebaseTokenService::class, function ($app) {
        return new \App\Services\FirebaseTokenService();
    });

    $this->app->singleton(\App\Services\FirestoreService::class, function ($app) {
    return new \App\Services\FirestoreService(
        'users', // Default collection (bisa disesuaikan)
        $app->make(\App\Services\FirebaseTokenService::class)
    );
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
