<?php

namespace LaraAgent\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LaraAgent\Agent\AgentBuilder;
use LaraAgent\Agent\AgentMemory;
use LaraAgent\Agent\AgentRunner;
use LaraAgent\Providers\ProviderFactory;
use LaraAgent\Tools\ToolRegistry;
use Illuminate\Support\Str;

class RunAgentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        private readonly string $task,
        private readonly string $providerName,
        private readonly array $toolNames,
        private readonly ?string $sessionId,
        private readonly array $config = [],
    ) {}

    public function handle(ToolRegistry $toolRegistry): void
    {
        $providerConfig = config("laragent.providers.{$this->providerName}", []);
        $provider = ProviderFactory::make($this->providerName, $providerConfig);

        $sessionId = $this->sessionId ?? Str::uuid()->toString();
        $driver = config('laragent.memory_driver', 'database');
        $memory = new AgentMemory($sessionId, $driver, [
            'agent_type' => 'queued',
            'provider'   => $this->providerName,
        ]);

        $runnerConfig = array_merge($this->config, [
            'tools' => $this->toolNames,
        ]);

        $runner = new AgentRunner(
            provider: $provider,
            tools: $toolRegistry,
            memory: $memory,
            config: $runnerConfig,
        );

        $runner->run($this->task, $this->config['context'] ?? []);
    }
}
