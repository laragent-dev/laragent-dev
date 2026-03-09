<?php

namespace Laragent\Cli;

/**
 * Handles all terminal output formatting for the Laragent CLI.
 * Uses ANSI escape codes for color — no external dependencies.
 */
class CliRenderer
{
    private bool $useColor;

    public function __construct(bool $useColor = true)
    {
        $this->useColor = $useColor && $this->terminalSupportsColor();
    }

    public function banner(string $provider, string $model): void
    {
        $this->line('');
        $this->line($this->bold('  Laragent CLI'));
        $this->line("  Provider: {$provider} ({$model})");
        $this->line('  Type your task, or /help for commands. /exit to quit.');
        $this->line('');
    }

    public function prompt(): string
    {
        echo $this->color("\n> ", 'cyan');

        return '';
    }

    public function thinking(): void
    {
        echo $this->color('  [thinking...]', 'yellow')."\n";
    }

    public function toolCall(string $name, array $params): void
    {
        $paramsStr = json_encode($params, JSON_UNESCAPED_SLASHES);
        echo $this->color("  [tool: {$name}]", 'blue')." {$paramsStr}\n";
    }

    public function toolResult(string $result): void
    {
        $preview = strlen($result) > 120 ? substr($result, 0, 120).'...' : $result;
        echo $this->color('  [result]', 'green')." {$preview}\n";
    }

    public function answer(string $answer): void
    {
        $this->line('');
        echo $answer."\n";
    }

    public function error(string $message): void
    {
        echo $this->color("  Error: {$message}", 'red')."\n";
    }

    public function info(string $message): void
    {
        echo $this->color("  {$message}", 'yellow')."\n";
    }

    public function success(string $message): void
    {
        echo $this->color("  {$message}", 'green')."\n";
    }

    public function divider(): void
    {
        $this->line($this->color('  '.str_repeat('-', 60), 'gray'));
    }

    public function swarmHeader(string $task, array $agents): void
    {
        $this->line('');
        $this->line($this->bold('  Laragent Swarm'));
        $this->line("  Task: {$task}");
        $this->line('  Agents: '.implode(', ', $agents));
        $this->divider();
    }

    public function swarmStep(string $agent, string $status): void
    {
        $color = match ($status) {
            'working' => 'yellow',
            'done' => 'green',
            'failed' => 'red',
            default => 'white',
        };
        echo $this->color("  [{$agent}]", $color)." {$status}\n";
    }

    public function help(): void
    {
        $this->line('');
        $this->line($this->bold('  Available commands:'));
        $commands = [
            '/help' => 'Show this help',
            '/exit' => 'Exit the CLI',
            '/clear' => 'Clear conversation memory',
            '/speak' => 'Speak your task (requires Whisper + sox)',
            '/tools [names]' => 'Set active tools, e.g. /tools database mailer',
            '/agent [name]' => 'Switch persona (support|data|content|workflow|dev|coding|testing|planning|docs|deploy)',
            '/provider [n]' => 'Switch provider (ollama|anthropic|openai)',
            '/swarm [task]' => 'Launch multi-agent swarm for complex tasks',
            '/session' => 'Show current session ID',
            '/status' => 'Show agent status (provider, tools, memory)',
        ];
        foreach ($commands as $cmd => $desc) {
            echo '  '.$this->color(str_pad($cmd, 20), 'cyan')." {$desc}\n";
        }
        $this->line('');
    }

    public function line(string $text = ''): void
    {
        echo $text."\n";
    }

    private function color(string $text, string $color): string
    {
        if (! $this->useColor) {
            return $text;
        }

        $codes = [
            'reset' => "\033[0m",
            'bold' => "\033[1m",
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'blue' => "\033[34m",
            'cyan' => "\033[36m",
            'white' => "\033[37m",
            'gray' => "\033[90m",
        ];

        $code = $codes[$color] ?? $codes['white'];
        $reset = $codes['reset'];

        return "{$code}{$text}{$reset}";
    }

    private function bold(string $text): string
    {
        return $this->color($text, 'bold');
    }

    private function terminalSupportsColor(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return false;
        }

        return function_exists('posix_isatty') && posix_isatty(STDOUT);
    }
}
