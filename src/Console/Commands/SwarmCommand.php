<?php

namespace Laragent\Console\Commands;

use Illuminate\Console\Command;
use Laragent\Cli\CliRenderer;
use Laragent\Swarm\AgentSwarm;
use Laragent\Swarm\SwarmOrchestrator;
use Laragent\Swarm\SwarmStep;

class SwarmCommand extends Command
{
    protected $signature = 'laragent:swarm
        {task : The complex task for the swarm to complete}
        {--template=feature : Swarm template (feature|api|frontend|audit)}
        {--agents=* : Custom agent sequence e.g. planning coding testing}
        {--no-color : Disable colored output}';

    protected $description = 'Launch a multi-agent swarm to collaboratively complete a complex task';

    public function handle(): int
    {
        $task = $this->argument('task');
        $template = $this->option('template');
        $renderer = new CliRenderer(! $this->option('no-color'));

        $orchestrator = new SwarmOrchestrator(app('laragent'));
        $swarm = $orchestrator->plan($task, $template);

        // Inject renderer for real-time output
        $swarm = new AgentSwarm(app('laragent'), $renderer);

        // Re-build steps from template
        $orchestrator2 = new SwarmOrchestrator(app('laragent'));
        $plannedSwarm = $orchestrator2->plan($task, $template);

        // Custom agents if specified
        $customAgents = $this->option('agents');
        if (! empty($customAgents)) {
            $swarm2 = new AgentSwarm(app('laragent'), $renderer);
            foreach ($customAgents as $agentName) {
                $swarm2->addStep(new SwarmStep(
                    agentName: $agentName,
                    role: $agentName,
                    task: $task,
                    tools: ['filesystem', 'database'],
                ));
            }
            $swarm2->withContext(['task' => $task]);
            $result = $swarm2->run();
        } else {
            $result = $plannedSwarm->run();
        }

        $this->line('');
        if ($result->success) {
            $renderer->success($result->summary());
            $renderer->line('');
            $renderer->line($result->finalOutput);
        } else {
            $renderer->error('Swarm did not complete all steps.');
            foreach ($result->steps as $step) {
                $status = $step->isCompleted() ? 'done' : 'failed';
                $renderer->swarmStep($step->agentName, $status);
            }
        }

        return $result->success ? self::SUCCESS : self::FAILURE;
    }
}
