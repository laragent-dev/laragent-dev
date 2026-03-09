<?php

use Illuminate\Support\Facades\Http;
use LaraAgent\Agent\AgentMemory;
use LaraAgent\Agent\AgentResponse;
use LaraAgent\Agent\AgentRunner;
use LaraAgent\Providers\OllamaProvider;
use LaraAgent\Tools\ToolRegistry;

beforeEach(function () {
    config(['laragent.memory_driver' => 'array']);
    config(['laragent.log_steps' => false]);
});

it('returns AgentResponse on successful run', function () {
    Http::fake([
        'localhost:11434/*' => Http::response([
            'message'           => ['role' => 'assistant', 'content' => '<final_answer>Hello world!</final_answer>'],
            'done'              => true,
            'done_reason'       => 'stop',
            'prompt_eval_count' => 10,
            'eval_count'        => 5,
        ]),
    ]);

    $provider = new OllamaProvider(['host' => 'http://localhost:11434', 'model' => 'llama3.2']);
    $registry = app(ToolRegistry::class);
    $memory = AgentMemory::new('test-session');

    $runner = new AgentRunner($provider, $registry, $memory, ['max_iterations' => 5]);
    $response = $runner->run('Say hello');

    expect($response)->toBeInstanceOf(AgentResponse::class);
    expect($response->wasSuccessful())->toBeTrue();
    expect($response->answer)->toBe('Hello world!');
});

it('exits after max iterations', function () {
    Http::fake([
        'localhost:11434/*' => Http::response([
            'message'           => ['role' => 'assistant', 'content' => 'Thinking...'],
            'done'              => true,
            'done_reason'       => 'stop',
            'prompt_eval_count' => 10,
            'eval_count'        => 5,
        ]),
    ]);

    $provider = new OllamaProvider(['host' => 'http://localhost:11434', 'model' => 'llama3.2']);
    $registry = app(ToolRegistry::class);
    $memory = AgentMemory::new('test-iter');

    $runner = new AgentRunner($provider, $registry, $memory, ['max_iterations' => 2]);
    $response = $runner->run('Keep thinking forever');

    expect($response->iterations)->toBe(2);
    expect($response->wasSuccessful())->toBeFalse();
});

it('parses final answer correctly', function () {
    Http::fake([
        'localhost:11434/*' => Http::response([
            'message'           => ['role' => 'assistant', 'content' => '<final_answer>The answer is 42</final_answer>'],
            'done'              => true,
            'done_reason'       => 'stop',
            'prompt_eval_count' => 10,
            'eval_count'        => 5,
        ]),
    ]);

    $provider = new OllamaProvider(['host' => 'http://localhost:11434', 'model' => 'llama3.2']);
    $registry = app(ToolRegistry::class);
    $memory = AgentMemory::new('test-final');

    $runner = new AgentRunner($provider, $registry, $memory);
    $response = $runner->run('What is the answer?');

    expect($response->answer)->toBe('The answer is 42');
});

it('handles tool call response gracefully when tool not registered', function () {
    $responses = [
        [
            'message'           => [
                'role'    => 'assistant',
                'content' => '<tool_call><name>nonexistent_tool</name><parameters>{}</parameters></tool_call>',
            ],
            'done'              => true,
            'done_reason'       => 'stop',
            'prompt_eval_count' => 10,
            'eval_count'        => 5,
        ],
        [
            'message'           => ['role' => 'assistant', 'content' => '<final_answer>Done despite error</final_answer>'],
            'done'              => true,
            'done_reason'       => 'stop',
            'prompt_eval_count' => 10,
            'eval_count'        => 5,
        ],
    ];

    $callCount = 0;
    Http::fake([
        'localhost:11434/*' => Http::sequence()
            ->push($responses[0])
            ->push($responses[1]),
    ]);

    $provider = new OllamaProvider(['host' => 'http://localhost:11434', 'model' => 'llama3.2']);
    $registry = app(ToolRegistry::class);
    $memory = AgentMemory::new('test-tool-error');

    $runner = new AgentRunner($provider, $registry, $memory, ['max_iterations' => 5]);
    $response = $runner->run('Use a nonexistent tool');

    // Should not crash the agent
    expect($response)->toBeInstanceOf(AgentResponse::class);
});

it('fires events during agent lifecycle', function () {
    Http::fake([
        'localhost:11434/*' => Http::response([
            'message'           => ['role' => 'assistant', 'content' => '<final_answer>Done</final_answer>'],
            'done'              => true,
            'done_reason'       => 'stop',
            'prompt_eval_count' => 10,
            'eval_count'        => 5,
        ]),
    ]);

    $startedFired = false;
    $completedFired = false;

    \Illuminate\Support\Facades\Event::listen(\LaraAgent\Events\AgentStarted::class, function () use (&$startedFired) {
        $startedFired = true;
    });

    \Illuminate\Support\Facades\Event::listen(\LaraAgent\Events\AgentCompleted::class, function () use (&$completedFired) {
        $completedFired = true;
    });

    $provider = new OllamaProvider(['host' => 'http://localhost:11434', 'model' => 'llama3.2']);
    $registry = app(ToolRegistry::class);
    $memory = AgentMemory::new('test-events');

    $runner = new AgentRunner($provider, $registry, $memory, ['max_iterations' => 5]);
    $runner->run('Test events');

    expect($startedFired)->toBeTrue();
    expect($completedFired)->toBeTrue();
});
