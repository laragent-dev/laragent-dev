<?php

namespace LaraAgent\Providers;

use Illuminate\Support\Facades\Http;

class OpenAIProvider extends BaseProvider
{
    public function complete(array $messages, array $options = []): ProviderResponse
    {
        $start = microtime(true);

        $baseUrl = rtrim($this->config['base_url'] ?? 'https://api.openai.com/v1', '/');
        $model = $options['model'] ?? $this->config['model'];
        $temperature = $options['temperature'] ?? 0.7;

        $response = Http::timeout($this->config['timeout'] ?? 60)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Content-Type'  => 'application/json',
            ])
            ->post("{$baseUrl}/chat/completions", [
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => $temperature,
                'max_tokens'  => $options['max_tokens'] ?? 4096,
            ]);

        if ($response->status() === 401) {
            throw new \RuntimeException(
                'Invalid API key. Check your OPENAI_API_KEY environment variable.'
            );
        }

        if ($response->failed()) {
            throw new \RuntimeException(
                'OpenAI request failed: ' . $response->body()
            );
        }

        $data = $response->json();
        $durationMs = (microtime(true) - $start) * 1000;

        $content = $data['choices'][0]['message']['content'] ?? '';
        $usage = $data['usage'] ?? [];

        return new ProviderResponse(
            content: $content,
            inputTokens: $usage['prompt_tokens'] ?? 0,
            outputTokens: $usage['completion_tokens'] ?? 0,
            model: $data['model'] ?? $model,
            finishReason: $data['choices'][0]['finish_reason'] ?? 'stop',
            durationMs: $durationMs,
        );
    }

    public function models(): array
    {
        $baseUrl = rtrim($this->config['base_url'] ?? 'https://api.openai.com/v1', '/');

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                ])
                ->get("{$baseUrl}/models");

            if ($response->failed()) {
                return [];
            }

            $data = $response->json();
            return array_column($data['data'] ?? [], 'id');
        } catch (\Exception $e) {
            return [];
        }
    }

    public function validateConfig(): void
    {
        if (empty($this->config['api_key'])) {
            throw new \InvalidArgumentException(
                'OpenAI API key is required. Set OPENAI_API_KEY in your .env file.'
            );
        }
    }
}
