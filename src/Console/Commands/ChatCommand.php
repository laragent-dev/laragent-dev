<?php

namespace Laragent\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laragent\Cli\CliRenderer;
use Laragent\Cli\SpeechToText\SttManager;
use Laragent\Tools\ToolRegistry;

class ChatCommand extends Command
{
    protected $signature = 'laragent:chat
        {--provider= : AI provider (ollama|anthropic|openai)}
        {--model= : Model override}
        {--agent= : Persona to start with}
        {--tools=* : Tools to enable}
        {--session= : Resume a previous session}
        {--no-color : Disable colored output}';

    protected $description = 'Start an interactive Laragent CLI session';

    private CliRenderer $renderer;
    private string $sessionId;
    private string $currentProvider;
    private ?string $currentModel;
    private array $currentTools = [];
    private ?string $currentAgent = null;
    private bool $useMemory = true;

    public function handle(): int
    {
        $this->renderer = new CliRenderer(!$this->option('no-color'));

        $this->currentProvider = $this->option('provider') ?? config('laragent.default_provider', 'ollama');
        $this->currentModel    = $this->option('model');
        $this->currentTools    = $this->option('tools') ?? [];
        $this->currentAgent    = $this->option('agent');
        $this->sessionId       = $this->option('session') ?? Str::uuid()->toString();

        $providerConfig = config("laragent.providers.{$this->currentProvider}", []);
        $model = $this->currentModel ?? $providerConfig['model'] ?? '?';

        $this->renderer->banner($this->currentProvider, $model);

        // Main input loop
        while (true) {
            $this->renderer->prompt();

            $input = $this->readLine();

            if ($input === null || $input === '' ) {
                continue;
            }

            $input = trim($input);

            if (empty($input)) {
                continue;
            }

            // Handle slash commands
            if (str_starts_with($input, '/')) {
                if ($this->handleCommand($input) === false) {
                    break; // /exit
                }
                continue;
            }

            // Run the agent
            $this->runAgent($input);
        }

        $this->renderer->line('');
        $this->renderer->info('Goodbye.');

        return self::SUCCESS;
    }

    private function handleCommand(string $input): bool|null
    {
        $parts   = explode(' ', $input, 2);
        $command = $parts[0];
        $args    = $parts[1] ?? '';

        switch ($command) {
            case '/exit':
            case '/quit':
            case '/q':
                return false;

            case '/help':
                $this->renderer->help();
                break;

            case '/clear':
                $this->sessionId = Str::uuid()->toString();
                $this->renderer->success('Memory cleared. New session started.');
                break;

            case '/speak':
                $this->handleSpeech();
                break;

            case '/tools':
                if (empty($args)) {
                    $registry = app(ToolRegistry::class);
                    $names = array_keys($registry->all());
                    $this->renderer->info('Available tools: ' . implode(', ', $names));
                    $this->renderer->info('Active tools: ' . (empty($this->currentTools) ? 'none' : implode(', ', $this->currentTools)));
                } else {
                    $this->currentTools = array_filter(explode(' ', $args));
                    $this->renderer->success('Tools set: ' . implode(', ', $this->currentTools));
                }
                break;

            case '/agent':
                if (!empty($args)) {
                    $this->currentAgent = trim($args);
                    $this->renderer->success("Persona switched to: {$this->currentAgent}");
                } else {
                    $this->renderer->info('Current agent: ' . ($this->currentAgent ?? 'custom'));
                    $this->renderer->info('Available: support, data, content, workflow, dev, coding, testing, planning, docs, deploy, research, design, uiux');
                }
                break;

            case '/provider':
                if (!empty($args)) {
                    $this->currentProvider = trim($args);
                    $providerConfig = config("laragent.providers.{$this->currentProvider}", []);
                    $model = $this->currentModel ?? $providerConfig['model'] ?? '?';
                    $this->renderer->success("Provider switched to: {$this->currentProvider} ({$model})");
                } else {
                    $this->renderer->info('Current provider: ' . $this->currentProvider);
                }
                break;

            case '/swarm':
                if (!empty($args)) {
                    $this->runSwarm($args);
                } else {
                    $this->renderer->info('Usage: /swarm <task description>');
                }
                break;

            case '/session':
                $this->renderer->info("Session ID: {$this->sessionId}");
                break;

            case '/status':
                $this->renderer->info("Provider: {$this->currentProvider}");
                $this->renderer->info("Model: " . ($this->currentModel ?? 'default'));
                $this->renderer->info("Tools: " . (empty($this->currentTools) ? 'none' : implode(', ', $this->currentTools)));
                $this->renderer->info("Persona: " . ($this->currentAgent ?? 'custom'));
                $this->renderer->info("Session: {$this->sessionId}");
                break;

            default:
                $this->renderer->error("Unknown command: {$command}. Type /help for commands.");
        }

        return true;
    }

    private function runAgent(string $task): void
    {
        $this->renderer->thinking();

        try {
            $manager = app('laragent');

            // Use persona if set
            if ($this->currentAgent) {
                $method = $this->resolvePersonaMethod($this->currentAgent);
                if (method_exists($manager, $method)) {
                    $persona = $manager->$method();
                    $builder = $persona->builder();
                } else {
                    $builder = $manager->make();
                }
            } else {
                $builder = $manager->make();
            }

            // Apply settings
            $builder->provider($this->currentProvider);
            if ($this->currentModel) {
                $builder->model($this->currentModel);
            }
            if (!empty($this->currentTools)) {
                $builder->tools($this->currentTools);
            }
            $builder->withMemory($this->sessionId);

            // Hook into tool calls for real-time display
            $builder->onToolCall(function (string $name, array $params) {
                $this->renderer->toolCall($name, $params);
            });

            $response = $builder->run($task);

            $this->renderer->answer($response->answer);

            if ($response->usedTools()) {
                $this->renderer->line('');
                $this->renderer->info($response->summary());
            }
        } catch (\Throwable $e) {
            $this->renderer->error($e->getMessage());
        }
    }

    private function handleSpeech(): void
    {
        $sttConfig = config('laragent.stt', []);
        $stt = new SttManager($sttConfig);

        if (!$stt->isAvailable()) {
            $this->renderer->error(
                "Speech-to-text not available. Install Whisper:\n" .
                "  pip install openai-whisper\n" .
                "  brew install sox  (macOS) OR  sudo apt install sox  (Linux)"
            );
            return;
        }

        $seconds = config('laragent.stt.seconds', 5);
        $this->renderer->info("Recording for {$seconds} seconds... speak now.");

        try {
            $driver   = $stt->driver();
            $audioFile = $driver->record($seconds);

            $this->renderer->info('Transcribing...');
            $transcript = $stt->transcribe($audioFile);

            @unlink($audioFile);

            if (empty($transcript)) {
                $this->renderer->error('No speech detected.');
                return;
            }

            $this->renderer->success("Detected: \"{$transcript}\"");
            $this->renderer->line('');

            // Run the transcribed text as a task
            $this->runAgent($transcript);
        } catch (\Throwable $e) {
            $this->renderer->error($e->getMessage());
        }
    }

    private function runSwarm(string $task): void
    {
        $this->renderer->info("Launching swarm for: {$task}");
        $this->call('laragent:swarm', ['task' => $task]);
    }

    private function resolvePersonaMethod(string $agent): string
    {
        return match (strtolower($agent)) {
            'support'   => 'support',
            'data'      => 'data',
            'content'   => 'content',
            'workflow'  => 'workflow',
            'dev'       => 'dev',
            'coding'    => 'coding',
            'testing'   => 'testing',
            'planning'  => 'planning',
            'docs'      => 'docs',
            'deploy'    => 'deploy',
            'research'  => 'research',
            'design'    => 'design',
            'uiux'      => 'uiux',
            default     => $agent,
        };
    }

    private function readLine(): ?string
    {
        if (function_exists('readline')) {
            $line = readline('');
            if ($line !== false && !empty(trim($line))) {
                readline_add_history($line);
            }
            return $line === false ? null : $line;
        }

        $line = fgets(STDIN);
        return $line === false ? null : rtrim($line, "\n");
    }
}
