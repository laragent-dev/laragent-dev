<?php

namespace LaraAgent\Console\Commands;

use Illuminate\Console\Command;
use LaraAgent\Models\AgentSession;

class AgentSessionsCommand extends Command
{
    protected $signature = 'agent:sessions {--limit=20 : Number of sessions to show}';
    protected $description = 'List recent agent sessions';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $sessions = AgentSession::latest()->limit($limit)->get();

        if ($sessions->isEmpty()) {
            $this->info('No agent sessions found.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Provider', 'Model', 'Status', 'Iterations', 'Tokens', 'Started'],
            $sessions->map(fn($s) => [
                substr($s->id, 0, 8) . '...',
                $s->name ?? '(unnamed)',
                $s->provider,
                $s->model,
                $s->status,
                $s->total_iterations,
                $s->total_tokens,
                $s->started_at?->diffForHumans() ?? 'never',
            ])
        );

        return self::SUCCESS;
    }
}
