<?php

namespace Laragent\Tools;

use Illuminate\Support\Facades\Artisan;

class ArtisanTool extends BaseTool
{
    public function name(): string
    {
        return 'run_artisan';
    }

    public function description(): string
    {
        return 'Run safe Laravel Artisan commands for cache clearing and queue management.';
    }

    public function parameters(): array
    {
        $safeCommands = config('laragent.safe_commands', []);

        return [
            'type' => 'object',
            'properties' => [
                'command' => [
                    'type' => 'string',
                    'description' => 'The artisan command to run. Allowed: '.implode(', ', $safeCommands),
                    'enum' => $safeCommands,
                ],
                'arguments' => ['type' => 'object', 'description' => 'Optional key-value arguments'],
            ],
            'required' => ['command'],
        ];
    }

    public function execute(array $params): string
    {
        $command = $params['command'] ?? '';
        $arguments = $params['arguments'] ?? [];

        $safeCommands = config('laragent.safe_commands', []);

        // Security: only allow safe commands
        if (! in_array($command, $safeCommands)) {
            return $this->error(
                "Command '{$command}' is not allowed. Allowed commands: ".implode(', ', $safeCommands)
            );
        }

        try {
            $exitCode = Artisan::call($command, $arguments);
            $output = Artisan::output();

            return "Command: {$command}\nExit code: {$exitCode}\nOutput:\n{$output}";
        } catch (\Exception $e) {
            return $this->error('Artisan command failed: '.$e->getMessage());
        }
    }
}
