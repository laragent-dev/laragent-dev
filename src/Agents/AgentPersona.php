<?php

namespace Laragent\Agents;

use Laragent\Agent\AgentBuilder;
use Laragent\Agent\AgentResponse;
use Laragent\Tools\ToolRegistry;

abstract class AgentPersona
{
    protected string $name = '';
    protected string $systemPrompt = '';
    protected array $defaultTools = [];
    protected string $defaultModel = '';

    protected ToolRegistry $toolRegistry;
    protected array $extraTools = [];
    protected string $extraSystem = '';

    public function __construct(ToolRegistry $toolRegistry)
    {
        $this->toolRegistry = $toolRegistry;
        $this->configure();
    }

    abstract protected function configure(): void;

    public function builder(): AgentBuilder
    {
        $builder = new AgentBuilder($this->toolRegistry, $this->name);
        $builder->tools(array_merge($this->defaultTools, $this->extraTools));

        $system = $this->systemPrompt;
        if ($this->extraSystem) {
            $system .= "\n\n" . $this->extraSystem;
        }
        $builder->system($system);

        if ($this->defaultModel) {
            $builder->model($this->defaultModel);
        }

        return $builder;
    }

    public function run(string $task): AgentResponse
    {
        return $this->builder()->run($task);
    }

    public function withTools(array $extra): static
    {
        $clone = clone $this;
        $clone->extraTools = array_merge($this->extraTools, $extra);
        return $clone;
    }

    public function withSystem(string $addition): static
    {
        $clone = clone $this;
        $clone->extraSystem = $addition;
        return $clone;
    }
}
