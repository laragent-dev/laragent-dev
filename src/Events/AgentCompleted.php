<?php

namespace LaraAgent\Events;

use LaraAgent\Agent\AgentResponse;
use LaraAgent\Models\AgentSession;

class AgentCompleted
{
    public function __construct(
        public readonly AgentSession $session,
        public readonly AgentResponse $response,
    ) {}
}
