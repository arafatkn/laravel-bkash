<?php

namespace Arafatkn\LaravelBkash;

use Arafatkn\LaravelBkash\Tokenized\Payment;
use Illuminate\Support\ServiceProvider;

class BkashServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/bkash.php', 'bkash');

        $this->app->singleton('bkash.tokenized.payment', fn () => new Payment());

        $this->app->alias('bkash.tokenized.payment', Payment::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/bkash.php' => $this->app->configPath('bkash.php'),
            ], 'bkash-config');
        }
    }
}
