<?php

namespace Laragent\Events;

use Laragent\Models\AgentSession;

class AgentStarted
{
    public function __construct(
        public readonly AgentSession $session,
        public readonly string $task,
    ) {}
}
