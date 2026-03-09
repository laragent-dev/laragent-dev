<?php

namespace Laragent\Cli\SpeechToText;

use RuntimeException;

/**
 * Speech-to-text using OpenAI Whisper (MIT licensed, runs locally, completely free).
 *
 * Install: pip install openai-whisper
 * Models: tiny (39MB), base (74MB), small (244MB), medium (769MB), large (1.5GB)
 *
 * Tiny model is recommended for CLI use — fast and accurate enough for commands.
 */
class WhisperDriver extends BaseSttDriver
{
    private string $model;

    private string $language;

    private string $whisperBin;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->model = $config['model'] ?? 'tiny';
        $this->language = $config['language'] ?? 'en';
        $this->whisperBin = $config['binary'] ?? 'whisper';
    }

    public function transcribe(string $audioFile): string
    {
        if (! file_exists($audioFile)) {
            throw new RuntimeException("Audio file not found: {$audioFile}");
        }

        $outputDir = sys_get_temp_dir();
        $baseName = pathinfo($audioFile, PATHINFO_FILENAME);

        // Run whisper CLI
        $cmd = sprintf(
            '%s %s --model %s --language %s --output_format txt --output_dir %s 2>/dev/null',
            escapeshellcmd($this->whisperBin),
            escapeshellarg($audioFile),
            escapeshellarg($this->model),
            escapeshellarg($this->language),
            escapeshellarg($outputDir)
        );

        exec($cmd, $output, $exitCode);

        $txtFile = $outputDir.'/'.$baseName.'.txt';

        if (! file_exists($txtFile)) {
            throw new RuntimeException(
                "Whisper transcription failed (exit code: {$exitCode}).\n".
                'Make sure Whisper is installed: pip install openai-whisper'
            );
        }

        $transcript = trim(file_get_contents($txtFile));
        @unlink($txtFile);

        return $transcript;
    }

    public function isAvailable(): bool
    {
        exec($this->whisperBin.' --help 2>/dev/null', $output, $exitCode);

        return $exitCode === 0;
    }
}
