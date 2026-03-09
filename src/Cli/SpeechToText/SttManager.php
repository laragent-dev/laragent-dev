<?php

namespace Laragent\Cli\SpeechToText;

use RuntimeException;

class SttManager
{
    private string $driver;
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->driver = $config['driver'] ?? 'whisper';
    }

    public function driver(): BaseSttDriver
    {
        return match ($this->driver) {
            'localai'     => new LocalAiSttDriver([
                'host'  => $this->config['host'] ?? 'http://localhost:8080',
                'model' => $this->config['model'] ?? 'whisper-1',
            ]),
            'whisper'     => new WhisperDriver([
                'model'    => $this->config['model'] ?? 'tiny',
                'language' => $this->config['language'] ?? 'en',
            ]),
            'whisper_cpp' => new WhisperCppDriver([
                'model'    => $this->config['model'] ?? 'tiny',
                'language' => $this->config['language'] ?? 'en',
            ]),
            default       => throw new RuntimeException("Unknown STT driver: {$this->driver}"),
        };
    }

    public function transcribe(string $audioFile): string
    {
        return $this->driver()->transcribe($audioFile);
    }

    public function isAvailable(): bool
    {
        return $this->driver()->isAvailable();
    }
}
