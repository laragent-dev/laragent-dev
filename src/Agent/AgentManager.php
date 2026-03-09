<?php

namespace LaraAgent\Agent;

use LaraAgent\Agents\ContentAgent;
use LaraAgent\Agents\DataAgent;
use LaraAgent\Agents\DevAgent;
use LaraAgent\Agents\SupportAgent;
use LaraAgent\Agents\WorkflowAgent;
use LaraAgent\Tools\ToolRegistry;

class AgentManager
{
    public function __construct(
        private readonly ToolRegistry $toolRegistry,
    ) {}

    public function make(?string $name = null): AgentBuilder
    {
        return new AgentBuilder($this->toolRegistry, $name);
    }

    public function run(string $task): AgentResponse
    {
        return $this->make()->run($task);
    }

    public function tools(array $toolNames): AgentBuilder
    {
        return $this->make()->tools($toolNames);
    }

    public function withMemory(?string $sessionId = null): AgentBuilder
    {
        return $this->make()->withMemory($sessionId);
    }

    public function pipeline(): AgentPipeline
    {
        return new AgentPipeline($this->toolRegistry);
    }

    public function support(): SupportAgent
    {
        return new SupportAgent($this->toolRegistry);
    }

    public function data(): DataAgent
    {
        return new DataAgent($this->toolRegistry);
    }

    public function content(): ContentAgent
    {
        return new ContentAgent($this->toolRegistry);
    }

    public function workflow(): WorkflowAgent
    {
        return new WorkflowAgent($this->toolRegistry);
    }

    public function dev(): DevAgent
    {
        return new DevAgent($this->toolRegistry);
    }

    public function fake(): \LaraAgent\Testing\AgentFake
    {
        $fake = new \LaraAgent\Testing\AgentFake($this->toolRegistry);
        app()->instance('laragent', $fake);
        return $fake;
    }

    public function getToolRegistry(): ToolRegistry
    {
        return $this->toolRegistry;
    }
}
