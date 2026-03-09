<?php

namespace Laragent\Providers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Laragent\Exceptions\ProviderException;

class OllamaProvider extends BaseProvider
{
    public function complete(array $messages, array $options = []): ProviderResponse
    {
        $start = microtime(true);

        $host = rtrim($this->config['host'], '/');
        $model = $options['model'] ?? $this->config['model'];
        $temperature = $options['temperature'] ?? 0.7;

        try {
            $response = Http::timeout($this->config['timeout'] ?? 120)
                ->post("{$host}/api/chat", [
                    'model'    => $model,
                    'messages' => $messages,
                    'stream'   => false,
                    'options'  => [
                        'temperature' => $temperature,
                    ],
                ]);
        } catch (ConnectionException $e) {
            throw new ProviderException(
                "Ollama is not running. Start it with: ollama serve\n" .
                "Or install it from: https://ollama.ai"
            );
        }

        if ($response->status() === 404) {
            $data = $response->json();
            if (isset($data['error']) && str_contains($data['error'], 'not found')) {
                throw ProviderException::modelNotFound($model);
            }
        }

        if ($response->failed()) {
            throw new \RuntimeException(
                "Ollama request failed: " . $response->body()
            );
        }

        $data = $response->json();
        $durationMs = (microtime(true) - $start) * 1000;

        $content = $data['message']['content'] ?? '';
        $promptTokens = $data['prompt_eval_count'] ?? 0;
        $evalTokens = $data['eval_count'] ?? 0;

        return new ProviderResponse(
            content: $content,
            inputTokens: $promptTokens,
            outputTokens: $evalTokens,
            model: $model,
            finishReason: $data['done_reason'] ?? 'stop',
            durationMs: $durationMs,
        );
    }

    public function models(): array
    {
        $host = rtrim($this->config['host'], '/');

        try {
            $response = Http::timeout(10)->get("{$host}/api/tags");

            if ($response->failed()) {
                return [];
            }

            $data = $response->json();
            return array_column($data['models'] ?? [], 'name');
        } catch (ConnectionException $e) {
            return [];
        }
    }

    public function isRunning(): bool
    {
        $host = rtrim($this->config['host'], '/');

        try {
            $response = Http::timeout(5)->get($host);
            return $response->successful() || $response->status() === 200;
        } catch (ConnectionException $e) {
            return false;
        }
    }

    public function validateConfig(): void
    {
        if (empty($this->config['host'])) {
            throw new \InvalidArgumentException('Ollama host is required');
        }

        if (empty($this->config['model'])) {
            trigger_error('No Ollama model specified, using llama3.2', E_USER_NOTICE);
        }
    }
}
