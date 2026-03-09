<?php

namespace Laragent\Events;

use Laragent\Agent\AgentResponse;
use Laragent\Models\AgentSession;

class AgentCompleted
{
    public function __construct(
        public readonly AgentSession $session,
        public readonly AgentResponse $response,
    ) {}
}
