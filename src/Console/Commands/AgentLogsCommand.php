<?php

namespace LaraAgent\Console\Commands;

use Illuminate\Console\Command;
use LaraAgent\Models\AgentLog;
use LaraAgent\Models\AgentSession;

class AgentLogsCommand extends Command
{
    protected $signature = 'agent:logs {session? : Session ID to show logs for} {--limit=50 : Number of logs to show}';
    protected $description = 'View agent logs';

    public function handle(): int
    {
        $sessionId = $this->argument('session');
        $limit = (int) $this->option('limit');

        $query = AgentLog::with('session')->latest()->limit($limit);

        if ($sessionId) {
            $session = AgentSession::where('id', 'LIKE', $sessionId . '%')->firstOrFail();
            $query->where('agent_session_id', $session->id);
        }

        $logs = $query->get();

        if ($logs->isEmpty()) {
            $this->info('No logs found.');
            return self::SUCCESS;
        }

        foreach ($logs as $log) {
            $this->line(sprintf(
                '[%s] [%s] %s: %s',
                $log->created_at->format('H:i:s'),
                strtoupper($log->type),
                $log->tool_name ?? 'agent',
                substr($log->content, 0, 100) . (strlen($log->content) > 100 ? '...' : '')
            ));
        }

        return self::SUCCESS;
    }
}
