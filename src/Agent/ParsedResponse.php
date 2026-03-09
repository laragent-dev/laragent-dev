<?php

namespace Laragent\Agent;

class ParsedResponse
{
    public function __construct(
        public readonly string $type,       // 'tool_call' | 'final_answer'
        public readonly string $content,    // The full answer or tool result
        public readonly ?string $toolName = null,
        public readonly array $toolParams = [],
    ) {}

    public function isToolCall(): bool
    {
        return $this->type === 'tool_call';
    }

    public function isFinalAnswer(): bool
    {
        return $this->type === 'final_answer';
    }
}
