<?php

namespace Arafatkn\LaravelBkash\Tests;

use Arafatkn\LaravelBkash\BkashServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            BkashServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'BkashTokenizedPayment' => \Arafatkn\LaravelBkash\Facades\TokenizedPayment::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('bkash.sandbox', true);
        $app['config']->set('bkash.app_key', 'test_app_key');
        $app['config']->set('bkash.app_secret', 'test_app_secret');
        $app['config']->set('bkash.username', 'test_username');
        $app['config']->set('bkash.password', 'test_password');
        $app['config']->set('bkash.debug', false);
        $app['config']->set('bkash.cache.refresh_token_lifetime', 86400 * 7);
    }
}
