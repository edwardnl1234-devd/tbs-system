<?php

namespace App\Providers;

use App\Models\Weighing;
use App\Observers\WeighingObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers
        Weighing::observe(WeighingObserver::class);
    }
}
