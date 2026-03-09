<?php

namespace Laragent\Agent;

use Laragent\Agents\CodingAgent;
use Laragent\Agents\ContentAgent;
use Laragent\Agents\DataAgent;
use Laragent\Agents\DeploymentAgent;
use Laragent\Agents\DesignAgent;
use Laragent\Agents\DevAgent;
use Laragent\Agents\DocumentationAgent;
use Laragent\Agents\PlanningAgent;
use Laragent\Agents\ResearchAgent;
use Laragent\Agents\SupportAgent;
use Laragent\Agents\TestingAgent;
use Laragent\Agents\UiUxAgent;
use Laragent\Agents\WorkflowAgent;
use Laragent\Tools\ToolRegistry;

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

    public function coding(): CodingAgent
    {
        return new CodingAgent($this->toolRegistry);
    }

    public function testing(): TestingAgent
    {
        return new TestingAgent($this->toolRegistry);
    }

    public function planning(): PlanningAgent
    {
        return new PlanningAgent($this->toolRegistry);
    }

    public function docs(): DocumentationAgent
    {
        return new DocumentationAgent($this->toolRegistry);
    }

    public function deploy(): DeploymentAgent
    {
        return new DeploymentAgent($this->toolRegistry);
    }

    public function research(): ResearchAgent
    {
        return new ResearchAgent($this->toolRegistry);
    }

    public function design(): DesignAgent
    {
        return new DesignAgent($this->toolRegistry);
    }

    public function uiux(): UiUxAgent
    {
        return new UiUxAgent($this->toolRegistry);
    }

    public function fake(): \Laragent\Testing\AgentFake
    {
        \Laragent\Testing\AgentFake::reset();
        $fake = new \Laragent\Testing\AgentFake($this->toolRegistry);
        app()->instance('laragent', $fake);
        \Laragent\Facades\Agent::clearResolvedInstances();

        return $fake;
    }

    public function getToolRegistry(): ToolRegistry
    {
        return $this->toolRegistry;
    }
}
