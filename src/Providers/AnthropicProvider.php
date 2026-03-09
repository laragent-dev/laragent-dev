<?php

namespace Laragent\Providers;

use Illuminate\Support\Facades\Http;
use Laragent\Exceptions\ProviderException;

class AnthropicProvider extends BaseProvider
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    private const API_VERSION = '2023-06-01';

    private const MAX_RETRIES = 3;

    public function complete(array $messages, array $options = []): ProviderResponse
    {
        $start = microtime(true);
        $model = $options['model'] ?? $this->config['model'];
        $temperature = $options['temperature'] ?? 0.7;

        // Extract system message if present
        $systemMessage = '';
        $chatMessages = [];
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemMessage = $message['content'];
            } else {
                $chatMessages[] = $message;
            }
        }

        $payload = [
            'model' => $model,
            'max_tokens' => $options['max_tokens'] ?? 4096,
            'messages' => $chatMessages,
            'temperature' => $temperature,
        ];

        if ($systemMessage) {
            $payload['system'] = $systemMessage;
        }

        $attempt = 0;
        $waitSeconds = 1;

        while ($attempt < self::MAX_RETRIES) {
            $response = Http::timeout($this->config['timeout'] ?? 60)
                ->withHeaders([
                    'x-api-key' => $this->config['api_key'],
                    'anthropic-version' => self::API_VERSION,
                    'content-type' => 'application/json',
                ])
                ->post(self::API_URL, $payload);

            if ($response->status() === 401) {
                throw ProviderException::invalidApiKey('Anthropic');
            }

            if (in_array($response->status(), [429, 529])) {
                $attempt++;
                if ($attempt >= self::MAX_RETRIES) {
                    throw new \RuntimeException(
                        'Anthropic API rate limit exceeded. Please try again later.'
                    );
                }
                sleep($waitSeconds);
                $waitSeconds *= 2;

                continue;
            }

            if ($response->failed()) {
                throw new \RuntimeException(
                    'Anthropic request failed: '.$response->body()
                );
            }

            break;
        }

        $data = $response->json();
        $durationMs = (microtime(true) - $start) * 1000;

        $content = $data['content'][0]['text'] ?? '';
        $usage = $data['usage'] ?? [];

        return new ProviderResponse(
            content: $content,
            inputTokens: $usage['input_tokens'] ?? 0,
            outputTokens: $usage['output_tokens'] ?? 0,
            model: $data['model'] ?? $model,
            finishReason: $data['stop_reason'] ?? 'stop',
            durationMs: $durationMs,
        );
    }

    public function models(): array
    {
        return [
            'claude-opus-4-6',
            'claude-sonnet-4-6',
            'claude-haiku-4-5',
        ];
    }

    public function validateConfig(): void
    {
        if (empty($this->config['api_key'])) {
            throw ProviderException::notConfigured('Anthropic', 'ANTHROPIC_API_KEY');
        }
    }
}
