<?php

namespace LaraAgent\Events;

use LaraAgent\Models\AgentSession;

class AgentToolResult
{
    public function __construct(
        public readonly AgentSession $session,
        public readonly string $toolName,
        public readonly string $result,
    ) {}
}
