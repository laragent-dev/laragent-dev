<?php

use Illuminate\Support\Facades\Http;
use Laragent\Providers\OllamaProvider;
use Laragent\Providers\ProviderResponse;

it('throws RuntimeException when ollama is not running', function () {
    Http::fake([
        'localhost:11434/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'),
    ]);

    $provider = new OllamaProvider(['host' => 'http://localhost:11434', 'model' => 'llama3.2']);

    expect(fn () => $provider->complete([['role' => 'user', 'content' => 'Hello']]))->toThrow(\RuntimeException::class, 'ollama serve');
});

it('returns helpful message for model not found', function () {
    Http::fake([
        'localhost:11434/api/chat' => Http::response(['error' => 'model llama99 not found'], 404),
    ]);

    $provider = new OllamaProvider(['host' => 'http://localhost:11434', 'model' => 'llama99']);

    expect(fn () => $provider->complete([['role' => 'user', 'content' => 'Hello']]))->toThrow(\RuntimeException::class, 'ollama pull');
});

it('returns ProviderResponse on successful completion', function () {
    Http::fake([
        'localhost:11434/api/chat' => Http::response([
            'message' => ['role' => 'assistant', 'content' => 'Hello there!'],
            'done' => true,
            'done_reason' => 'stop',
            'prompt_eval_count' => 15,
            'eval_count' => 8,
        ]),
    ]);

    $provider = new OllamaProvider(['host' => 'http://localhost:11434', 'model' => 'llama3.2']);
    $response = $provider->complete([['role' => 'user', 'content' => 'Hello']]);

    expect($response)->toBeInstanceOf(ProviderResponse::class);
    expect($response->content)->toBe('Hello there!');
    expect($response->inputTokens)->toBe(15);
    expect($response->outputTokens)->toBe(8);
    expect($response->model)->toBe('llama3.2');
});

it('returns false for isRunning when ollama is not running', function () {
    Http::fake([
        'localhost:11434' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused'),
    ]);

    $provider = new OllamaProvider(['host' => 'http://localhost:11434', 'model' => 'llama3.2']);
    expect($provider->isRunning())->toBeFalse();
});

it('returns list of models', function () {
    Http::fake([
        'localhost:11434/api/tags' => Http::response([
            'models' => [
                ['name' => 'llama3.2'],
                ['name' => 'mistral'],
            ],
        ]),
    ]);

    $provider = new OllamaProvider(['host' => 'http://localhost:11434', 'model' => 'llama3.2']);
    $models = $provider->models();

    expect($models)->toContain('llama3.2');
    expect($models)->toContain('mistral');
});
