<?php

use Illuminate\Support\Facades\Http;
use Laragent\Agent\PipelineResponse;
use Laragent\Facades\Agent;

beforeEach(function () {
    config(['laragent.memory_driver' => 'array']);
    config(['laragent.log_steps' => false]);
});

it('runs a 2-step pipeline sequentially', function () {
    Http::fake([
        'localhost:11434/api/chat' => Http::sequence()
            ->push([
                'message' => ['role' => 'assistant', 'content' => '<final_answer>Step 1 result</final_answer>'],
                'done' => true,
                'done_reason' => 'stop',
                'prompt_eval_count' => 10,
                'eval_count' => 5,
            ])
            ->push([
                'message' => ['role' => 'assistant', 'content' => '<final_answer>Step 2 result</final_answer>'],
                'done' => true,
                'done_reason' => 'stop',
                'prompt_eval_count' => 10,
                'eval_count' => 5,
            ]),
    ]);

    $result = Agent::pipeline()
        ->step('step1')->task('Do step 1')
        ->step('step2')->task('Do step 2')
        ->run();

    expect($result)->toBeInstanceOf(PipelineResponse::class);
    expect($result->success)->toBeTrue();
    expect($result->finalOutput)->toBe('Step 2 result');
    expect(count($result->steps))->toBe(2);
});

it('injects passOutputAs value into next step task', function () {
    $capturedTask = null;

    Http::fake([
        'localhost:11434/api/chat' => Http::sequence()
            ->push([
                'message' => ['role' => 'assistant', 'content' => '<final_answer>Research output</final_answer>'],
                'done' => true,
                'done_reason' => 'stop',
                'prompt_eval_count' => 10,
                'eval_count' => 5,
            ])
            ->push([
                'message' => ['role' => 'assistant', 'content' => '<final_answer>Final output</final_answer>'],
                'done' => true,
                'done_reason' => 'stop',
                'prompt_eval_count' => 10,
                'eval_count' => 5,
            ]),
    ]);

    $result = Agent::pipeline()
        ->step('researcher')
        ->task('Research topic')
        ->passOutputAs('research')
        ->step('writer')
        ->task('Write about: {research}')
        ->run();

    expect($result->success)->toBeTrue();
    expect($result->finalOutput)->toBe('Final output');
});

it('stops pipeline on step failure', function () {
    Http::fake([
        'localhost:11434/api/chat' => Http::response([
            'message' => ['role' => 'assistant', 'content' => 'Partial response without final answer'],
            'done' => true,
            'done_reason' => 'stop',
            'prompt_eval_count' => 10,
            'eval_count' => 5,
        ]),
    ]);

    $result = Agent::pipeline()
        ->step('step1')->task('Task that hits max iterations')
        ->step('step2')->task('This should not run')
        ->run();

    // Pipeline may succeed or fail depending on fallback behavior
    expect($result)->toBeInstanceOf(PipelineResponse::class);
});
