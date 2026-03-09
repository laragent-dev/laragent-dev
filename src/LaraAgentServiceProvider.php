<?php

namespace LaraAgent;

use Illuminate\Support\ServiceProvider;
use LaraAgent\Agent\AgentManager;
use LaraAgent\Console\Commands\AgentInstallCommand;
use LaraAgent\Console\Commands\AgentLogsCommand;
use LaraAgent\Console\Commands\AgentRunCommand;
use LaraAgent\Console\Commands\AgentSessionsCommand;
use LaraAgent\Tools\ArtisanTool;
use LaraAgent\Tools\DatabaseTool;
use LaraAgent\Tools\FilesystemTool;
use LaraAgent\Tools\HttpTool;
use LaraAgent\Tools\MailerTool;
use LaraAgent\Tools\ToolRegistry;

class LaraAgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laragent.php', 'laragent');

        // Register tool registry as singleton
        $this->app->singleton(ToolRegistry::class, function () {
            $registry = new ToolRegistry();

            // Register all default tools
            $registry->register(new DatabaseTool());
            $registry->register(new MailerTool());
            $registry->register(new HttpTool());
            $registry->register(new ArtisanTool());
            $registry->register(new FilesystemTool());

            return $registry;
        });

        // Register AgentManager as singleton bound to 'laragent'
        $this->app->singleton('laragent', function ($app) {
            return new AgentManager($app->make(ToolRegistry::class));
        });

        $this->app->alias('laragent', AgentManager::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__ . '/../config/laragent.php' => config_path('laragent.php'),
            ], 'laragent-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'laragent-migrations');

            // Register Artisan commands
            $this->commands([
                AgentInstallCommand::class,
                AgentRunCommand::class,
                AgentSessionsCommand::class,
                AgentLogsCommand::class,
            ]);
        }

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
