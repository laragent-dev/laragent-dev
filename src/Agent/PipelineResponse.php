<?php

namespace Laragent\Agent;

class PipelineResponse
{
    public function __construct(
        public readonly array $steps,
        public readonly string $finalOutput,
        public readonly int $totalIterations,
        public readonly int $totalTokens,
        public readonly float $totalDurationMs,
        public readonly bool $success,
    ) {}

    public function toArray(): array
    {
        return [
            'steps'             => array_map(fn($s) => $s->toArray(), $this->steps),
            'final_output'      => $this->finalOutput,
            'total_iterations'  => $this->totalIterations,
            'total_tokens'      => $this->totalTokens,
            'total_duration_ms' => $this->totalDurationMs,
            'success'           => $this->success,
        ];
    }
}
