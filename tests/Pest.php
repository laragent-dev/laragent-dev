<?php

use Laragent\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

function mockOllamaResponse(string $content = '<final_answer>Done</final_answer>'): void
{
    \Illuminate\Support\Facades\Http::fake([
        '*localhost:11434*' => \Illuminate\Support\Facades\Http::response([
            'message' => ['role' => 'assistant', 'content' => $content],
            'done' => true,
            'done_reason' => 'stop',
            'prompt_eval_count' => 10,
            'eval_count' => 20,
        ]),
    ]);
}

// Backwards-compat alias
function mockOllama(string $response = 'Task completed.'): void
{
    mockOllamaResponse($response);
}
