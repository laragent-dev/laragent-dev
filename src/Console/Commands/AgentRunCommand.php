<?php

namespace Laragent\Console\Commands;

use Illuminate\Console\Command;
use Laragent\Facades\Agent;

class AgentRunCommand extends Command
{
    protected $signature = 'agent:run
        {task : The task for the agent to perform}
        {--provider= : Provider to use (ollama, anthropic, openai)}
        {--tools=* : Tools to enable (database, mailer, http, artisan, filesystem)}
        {--memory= : Session ID for persistent memory}';

    protected $description = 'Run an AI agent task from the command line';

    public function handle(): int
    {
        $task = $this->argument('task');
        $provider = $this->option('provider');
        $tools = $this->option('tools');
        $memory = $this->option('memory');

        $this->info("Running agent task: {$task}");
        $this->line('');

        $builder = Agent::make();

        if ($provider) {
            $builder->provider($provider);
        }

        if (!empty($tools)) {
            $builder->tools($tools);
        }

        if ($memory) {
            $builder->withMemory($memory);
        }

        try {
            $result = $builder->run($task);

            $this->info('Agent Response:');
            $this->line('─────────────────────────────────────');
            $this->line($result->answer);
            $this->line('─────────────────────────────────────');
            $this->line($result->summary());

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Agent failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
