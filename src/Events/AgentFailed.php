<?php

namespace LaraAgent\Events;

use LaraAgent\Models\AgentSession;

class AgentFailed
{
    public function __construct(
        public readonly AgentSession $session,
        public readonly \Throwable $exception,
    ) {}
}
