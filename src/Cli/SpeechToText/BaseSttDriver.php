<?php

namespace Laragent\Cli\SpeechToText;

abstract class BaseSttDriver
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    abstract public function transcribe(string $audioFile): string;
    abstract public function isAvailable(): bool;

    /**
     * Record audio to a temp file and return the path.
     * Uses sox (cross-platform, free) to capture microphone input.
     */
    public function record(int $seconds = 5): string
    {
        $tmpFile = sys_get_temp_dir() . '/laragent_audio_' . time() . '.wav';

        // sox -d records from default microphone
        // -r 16000: 16kHz sample rate (ideal for Whisper)
        // -c 1: mono
        // trim 0 {seconds}: stop after N seconds
        $cmd = "sox -d -r 16000 -c 1 {$tmpFile} trim 0 {$seconds} 2>/dev/null";
        exec($cmd, $output, $exitCode);

        if (!file_exists($tmpFile)) {
            throw new \RuntimeException(
                "Audio recording failed. Is 'sox' installed?\n" .
                "  macOS:  brew install sox\n" .
                "  Linux:  sudo apt install sox"
            );
        }

        return $tmpFile;
    }

    /**
     * Record audio until the user presses Enter, then return path.
     */
    public function recordUntilEnter(): string
    {
        $tmpFile = sys_get_temp_dir() . '/laragent_audio_' . time() . '.wav';
        $pidFile = sys_get_temp_dir() . '/laragent_sox_' . time() . '.pid';

        // Start sox in background, recording indefinitely
        $cmd = "sox -d -r 16000 -c 1 {$tmpFile} 2>/dev/null & echo $! > {$pidFile}";
        exec($cmd);

        return ['file' => $tmpFile, 'pid_file' => $pidFile];
    }
}
