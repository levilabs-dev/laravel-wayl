<?php

declare(strict_types=1);

namespace LeviLabs\LaravelWayl\Tests;

use LeviLabs\LaravelWayl\WaylServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            WaylServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('wayl.default', 'default');
        $app['config']->set('wayl.merchants.default', [
            'api_key' => 'test-wayl-key',
            'base_url' => 'https://api.thewayl.com',
        ]);
        $app['config']->set('wayl.environment', 'test');
        $app['config']->set('wayl.currency', 'IQD');
        $app['config']->set('wayl.webhook_url', 'https://example.test/wayl/webhook');
        $app['config']->set('wayl.webhook_secret', '1234567890');
        $app['config']->set('wayl.redirection_url', 'https://example.test/checkout/return');
        $app['config']->set('wayl.link_expires_in', '1h');
        $app['config']->set('wayl.http', [
            'timeout' => 15,
            'retry_times' => 1,
            'retry_sleep_ms' => 200,
        ]);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('cache.default', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }
}
