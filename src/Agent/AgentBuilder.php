<?php

namespace LaraAgent\Agent;

use LaraAgent\Jobs\RunAgentJob;
use LaraAgent\Providers\BaseProvider;
use LaraAgent\Providers\ProviderFactory;
use LaraAgent\Tools\ToolRegistry;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AgentBuilder
{
    private ?string $name = null;
    private ?string $providerName = null;
    private ?string $modelOverride = null;
    private array $toolNames = [];
    private ?string $sessionId = null;
    private string $systemPrompt = '';
    private float $temperature = 0.7;
    private int $maxIterations = 0;
    private array $context = [];
    private ?callable $onToolCall = null;
    private ?callable $onComplete = null;
    private bool $isAsync = false;

    private ToolRegistry $toolRegistry;

    public function __construct(ToolRegistry $toolRegistry, ?string $name = null)
    {
        $this->toolRegistry = $toolRegistry;
        $this->name = $name;
        $this->maxIterations = config('laragent.max_iterations', 10);
    }

    public function provider(string $name): static
    {
        $this->providerName = $name;
        return $this;
    }

    public function model(string $model): static
    {
        $this->modelOverride = $model;
        return $this;
    }

    public function tools(array $toolNames): static
    {
        $this->toolNames = $toolNames;
        return $this;
    }

    public function withMemory(?string $sessionId = null): static
    {
        $this->sessionId = $sessionId ?? Str::uuid()->toString();
        return $this;
    }

    public function system(string $prompt): static
    {
        $this->systemPrompt = $prompt;
        return $this;
    }

    public function temperature(float $temp): static
    {
        if ($temp < 0.0 || $temp > 1.0) {
            throw new InvalidArgumentException('Temperature must be between 0.0 and 1.0');
        }
        $this->temperature = $temp;
        return $this;
    }

    public function maxIterations(int $n): static
    {
        $this->maxIterations = $n;
        return $this;
    }

    public function context(array $data): static
    {
        $this->context = array_merge($this->context, $data);
        return $this;
    }

    public function onToolCall(callable $fn): static
    {
        $this->onToolCall = $fn;
        return $this;
    }

    public function onComplete(callable $fn): static
    {
        $this->onComplete = $fn;
        return $this;
    }

    public function async(): static
    {
        $this->isAsync = true;
        return $this;
    }

    public function run(string $task): AgentResponse
    {
        $provider = $this->resolveProvider();
        $memory = $this->resolveMemory();

        $runnerConfig = [
            'tools'          => $this->toolNames,
            'temperature'    => $this->temperature,
            'max_iterations' => $this->maxIterations,
            'system'         => $this->systemPrompt,
        ];

        if ($this->modelOverride) {
            $runnerConfig['model'] = $this->modelOverride;
        }

        $runner = new AgentRunner(
            provider: $provider,
            tools: $this->toolRegistry,
            memory: $memory,
            config: $runnerConfig,
        );

        $response = $runner->run($task, $this->context);

        if ($this->onComplete) {
            ($this->onComplete)($response);
        }

        return $response;
    }

    public function dispatch(string $task): string
    {
        $jobId = Str::uuid()->toString();

        RunAgentJob::dispatch(
            task: $task,
            providerName: $this->providerName ?? config('laragent.default_provider', 'ollama'),
            toolNames: $this->toolNames,
            sessionId: $this->sessionId,
            config: [
                'temperature'    => $this->temperature,
                'max_iterations' => $this->maxIterations,
                'system'         => $this->systemPrompt,
                'model'          => $this->modelOverride,
                'context'        => $this->context,
                'job_id'         => $jobId,
            ]
        );

        return $jobId;
    }

    private function resolveProvider(): BaseProvider
    {
        $providerName = $this->providerName ?? config('laragent.default_provider', 'ollama');
        $providerConfig = config("laragent.providers.{$providerName}", []);

        return ProviderFactory::make($providerName, $providerConfig);
    }

    private function resolveMemory(): AgentMemory
    {
        $sessionId = $this->sessionId ?? Str::uuid()->toString();
        $driver = config('laragent.memory_driver', 'database');

        $sessionData = [
            'name'       => $this->name,
            'agent_type' => 'custom',
            'provider'   => $this->providerName ?? config('laragent.default_provider', 'ollama'),
            'model'      => $this->modelOverride ?? '',
        ];

        return new AgentMemory($sessionId, $driver, $sessionData);
    }
}
