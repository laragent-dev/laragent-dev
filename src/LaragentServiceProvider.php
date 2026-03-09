<?php

namespace Laragent;

use Illuminate\Support\ServiceProvider;
use Laragent\Agent\AgentManager;
use Laragent\Console\Commands\AgentInstallCommand;
use Laragent\Console\Commands\AgentLogsCommand;
use Laragent\Console\Commands\AgentRunCommand;
use Laragent\Console\Commands\AgentSessionsCommand;
use Laragent\Console\Commands\ChatCommand;
use Laragent\Console\Commands\SwarmCommand;
use Laragent\Tools\ArtisanTool;
use Laragent\Tools\DatabaseTool;
use Laragent\Tools\FilesystemTool;
use Laragent\Tools\HttpTool;
use Laragent\Tools\MailerTool;
use Laragent\Tools\ToolRegistry;

class LaragentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laragent.php', 'laragent');

        // Register tool registry as singleton
        $this->app->singleton(ToolRegistry::class, function () {
            $registry = new ToolRegistry;

            // Register all default tools
            $registry->register(new DatabaseTool);
            $registry->register(new MailerTool);
            $registry->register(new HttpTool);
            $registry->register(new ArtisanTool);
            $registry->register(new FilesystemTool);

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
                __DIR__.'/../config/laragent.php' => config_path('laragent.php'),
            ], 'laragent-config');

            // Publish migrations
            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'laragent-migrations');

            // Register Artisan commands
            $this->commands([
                AgentInstallCommand::class,
                AgentRunCommand::class,
                AgentSessionsCommand::class,
                AgentLogsCommand::class,
                ChatCommand::class,
                SwarmCommand::class,
            ]);
        }

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
