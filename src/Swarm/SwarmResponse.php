<?php

namespace Laragent\Swarm;

class SwarmResponse
{
    public function __construct(
        public readonly array $steps,
        public readonly string $finalOutput,
        public readonly int $totalIterations,
        public readonly int $totalTokens,
        public readonly float $totalDurationMs,
        public readonly bool $success,
        public readonly array $filesCreated = [],
    ) {}

    public function summary(): string
    {
        $count = count($this->steps);
        $ms = round($this->totalDurationMs);

        return "Swarm completed: {$count} agents, {$this->totalIterations} iterations, {$ms}ms";
    }
}
