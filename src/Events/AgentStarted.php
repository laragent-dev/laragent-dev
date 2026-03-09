<?php

namespace LaraAgent\Events;

use LaraAgent\Models\AgentSession;

class AgentStarted
{
    public function __construct(
        public readonly AgentSession $session,
        public readonly string $task,
    ) {}
}
