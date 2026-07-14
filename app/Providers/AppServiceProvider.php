<?php

namespace App\Providers;

use App\Models\Source;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        RateLimiter::for('webhooks', function (Request $request) {
            $source = $request->route('source');

            $key = $source instanceof Source ? $source->getKey() : ((string) $source ?: $request->ip());

            return Limit::perMinute(120)->by($key);
        });
    }
}
