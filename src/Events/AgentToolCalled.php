<?php

namespace Laragent\Events;

use Laragent\Models\AgentSession;

class AgentToolCalled
{
    public function __construct(
        public readonly AgentSession $session,
        public readonly string $toolName,
        public readonly array $params,
    ) {}
}
