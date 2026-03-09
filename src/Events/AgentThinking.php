<?php

namespace Laragent\Events;

use Laragent\Models\AgentSession;

class AgentThinking
{
    public function __construct(
        public readonly AgentSession $session,
        public readonly int $iteration,
    ) {}
}
