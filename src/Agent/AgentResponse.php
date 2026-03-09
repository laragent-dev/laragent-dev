<?php

namespace Laragent\Agent;

class AgentResponse
{
    public function __construct(
        public readonly string $answer,
        public readonly string $sessionId,
        public readonly array $toolCalls,
        public readonly int $iterations,
        public readonly int $tokensUsed,
        public readonly float $durationMs,
        public readonly bool $success,
        public readonly ?string $error = null,
    ) {}

    public function wasSuccessful(): bool
    {
        return $this->success;
    }

    public function usedTools(): bool
    {
        return count($this->toolCalls) > 0;
    }

    public function summary(): string
    {
        $toolCount = count($this->toolCalls);
        $ms = round($this->durationMs);

        return "Completed in {$this->iterations} iteration(s), {$toolCount} tool call(s), {$ms}ms";
    }

    public function toArray(): array
    {
        return [
            'answer' => $this->answer,
            'session_id' => $this->sessionId,
            'tool_calls' => $this->toolCalls,
            'iterations' => $this->iterations,
            'tokens_used' => $this->tokensUsed,
            'duration_ms' => $this->durationMs,
            'success' => $this->success,
            'error' => $this->error,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
