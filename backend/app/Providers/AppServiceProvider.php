<?php

namespace App\Providers;

use App\Services\Dns\NativeMxResolver;
use Closure;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            NativeMxResolver::class,
            static fn (): NativeMxResolver => new NativeMxResolver(Closure::fromCallable('dns_get_record')),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
