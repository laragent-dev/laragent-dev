<?php

namespace Laragent\Swarm;

use Laragent\Agent\AgentResponse;

class SwarmStep
{
    public function __construct(
        public readonly string $agentName,
        public readonly string $role,
        public readonly string $task,
        public readonly array $tools = [],
        public ?AgentResponse $response = null,
        public string $status = 'pending',
    ) {}

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function output(): string
    {
        return $this->response?->answer ?? '';
    }
}
