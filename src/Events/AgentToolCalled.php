<?php

namespace LaraAgent\Events;

use LaraAgent\Models\AgentSession;

class AgentToolCalled
{
    public function __construct(
        public readonly AgentSession $session,
        public readonly string $toolName,
        public readonly array $params,
    ) {}
}
