<?php

namespace Laragent\Tests;

use Laragent\LaragentServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LaragentServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Use SQLite for tests
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Use Ollama as default provider for tests
        $app['config']->set('laragent.default_provider', 'ollama');
        $app['config']->set('laragent.memory_driver', 'array');
        $app['config']->set('laragent.log_steps', false);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'testing']);
    }
}
