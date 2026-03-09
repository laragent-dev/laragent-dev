<?php

namespace Laragent\Cli\SpeechToText;

use RuntimeException;

/**
 * Speech-to-text using whisper.cpp (C++ port of Whisper, MIT licensed, no Python needed).
 *
 * Install: https://github.com/ggerganov/whisper.cpp
 *   git clone https://github.com/ggerganov/whisper.cpp
 *   cd whisper.cpp && make
 *   ./models/download-ggml-model.sh tiny.en
 *
 * Then set 'binary' to the path of the ./main executable.
 */
class WhisperCppDriver extends BaseSttDriver
{
    private string $binary;
    private string $model;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->binary = $config['binary'] ?? '/usr/local/bin/whisper-cpp';
        $this->model  = $config['model_path'] ?? '';
    }

    public function transcribe(string $audioFile): string
    {
        if (empty($this->model)) {
            throw new RuntimeException('whisper.cpp model path not configured in laragent.stt.whisper_cpp.model_path');
        }

        $cmd = sprintf(
            '%s -m %s -f %s -nt 2>/dev/null',
            escapeshellcmd($this->binary),
            escapeshellarg($this->model),
            escapeshellarg($audioFile)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || empty($output)) {
            throw new RuntimeException("whisper.cpp transcription failed (exit code: {$exitCode}).");
        }

        return trim(implode(' ', $output));
    }

    public function isAvailable(): bool
    {
        exec($this->binary . ' --help 2>/dev/null', $output, $exitCode);
        return file_exists($this->binary);
    }
}
