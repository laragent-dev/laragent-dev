<?php

namespace Laragent\Events;

use Laragent\Models\AgentSession;

class AgentFailed
{
    public function __construct(
        public readonly AgentSession $session,
        public readonly \Throwable $exception,
    ) {}
}
