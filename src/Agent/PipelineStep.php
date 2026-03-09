<?php

namespace Laragent\Agent;

class PipelineStep
{
    private string $agentName;
    private string $task = '';
    private array $toolNames = [];
    private ?string $providerName = null;
    private ?string $passOutputAs = null;
    private ?AgentPipeline $pipeline;

    public function __construct(string $agentName, AgentPipeline $pipeline)
    {
        $this->agentName = $agentName;
        $this->pipeline = $pipeline;
    }

    public function task(string $task): static
    {
        $this->task = $task;
        return $this;
    }

    public function tools(array $tools): static
    {
        $this->toolNames = $tools;
        return $this;
    }

    public function provider(string $provider): static
    {
        $this->providerName = $provider;
        return $this;
    }

    public function passOutputAs(string $key): static
    {
        $this->passOutputAs = $key;
        return $this;
    }

    public function getTask(): string
    {
        return $this->task;
    }

    public function getToolNames(): array
    {
        return $this->toolNames;
    }

    public function getProviderName(): ?string
    {
        return $this->providerName;
    }

    public function getOutputKey(): ?string
    {
        return $this->passOutputAs;
    }

    public function getAgentName(): string
    {
        return $this->agentName;
    }

    // Delegate to pipeline for chaining
    public function step(string $agentName): static
    {
        return $this->pipeline->step($agentName);
    }

    public function run(): PipelineResponse
    {
        return $this->pipeline->run();
    }

    public function dispatch(): string
    {
        return $this->pipeline->dispatch();
    }
}
