<?php

namespace Laragent\Cli\SpeechToText;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Speech-to-text via LocalAI — runs locally, completely free, no Python required.
 *
 * LocalAI is an open-source, Ollama-like server that hosts AI models locally
 * and exposes an OpenAI-compatible HTTP API.
 *
 * Install LocalAI:
 *   docker run -p 8080:8080 localai/localai:latest whisper
 *   OR:  curl https://localai.io/install.sh | sh
 *
 * Models (Whisper variants):
 *   whisper-1 (default), whisper-base, whisper-small, whisper-medium, whisper-large
 *
 * LocalAI docs: https://localai.io
 */
class LocalAiSttDriver extends BaseSttDriver
{
    private string $host;

    private string $model;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->host = rtrim($config['host'] ?? 'http://localhost:8080', '/');
        $this->model = $config['model'] ?? 'whisper-1';
    }

    public function transcribe(string $audioFile): string
    {
        if (! file_exists($audioFile)) {
            throw new RuntimeException("Audio file not found: {$audioFile}");
        }

        $response = Http::timeout(60)
            ->attach('file', file_get_contents($audioFile), basename($audioFile))
            ->post("{$this->host}/v1/audio/transcriptions", [
                'model' => $this->model,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                "LocalAI transcription failed (HTTP {$response->status()}): ".$response->body()."\n".
                'Make sure LocalAI is running: docker run -p 8080:8080 localai/localai:latest whisper'
            );
        }

        return trim($response->json('text') ?? '');
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(3)->get("{$this->host}/v1/models");

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
