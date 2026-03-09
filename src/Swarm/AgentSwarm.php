<?php

namespace Laragent\Swarm;

use Laragent\Agent\AgentManager;
use Laragent\Cli\CliRenderer;

/**
 * Coordinates multiple specialized agents working collaboratively on a complex task.
 *
 * Agents share a workspace directory and can read each other's outputs.
 * The orchestrator decides which agents to use and in what order.
 */
class AgentSwarm
{
    private array $steps = [];

    private array $sharedContext = [];

    private ?CliRenderer $renderer;

    private string $workspaceDir;

    public function __construct(
        private readonly AgentManager $manager,
        ?CliRenderer $renderer = null,
    ) {
        $this->renderer = $renderer;
        $this->workspaceDir = 'agent-swarm-'.date('Y-m-d-His');
    }

    /**
     * Add a step to the swarm.
     */
    public function addStep(SwarmStep $step): static
    {
        $this->steps[] = $step;

        return $this;
    }

    /**
     * Set shared context available to all agents.
     */
    public function withContext(array $context): static
    {
        $this->sharedContext = $context;

        return $this;
    }

    /**
     * Run all steps sequentially, passing outputs between agents.
     */
    public function run(): SwarmResponse
    {
        $start = microtime(true);
        $totalIterations = 0;
        $totalTokens = 0;
        $context = $this->sharedContext;
        $context['workspace'] = $this->workspaceDir;

        foreach ($this->steps as $index => $step) {
            $this->renderer?->swarmStep($step->agentName, 'working');

            // Interpolate previous outputs into task
            $task = $step->task;
            foreach ($context as $key => $value) {
                if (is_string($value)) {
                    $task = str_replace('{'.$key.'}', $value, $task);
                }
            }

            try {
                // Resolve persona/agent
                $builder = $this->resolveAgent($step->agentName, $step->role);
                $builder->provider(config('laragent.default_provider', 'ollama'));
                $builder->tools($step->tools);
                $builder->context($context);

                $response = $builder->run($task);

                $step->response = $response;
                $step->status = $response->wasSuccessful() ? 'completed' : 'failed';

                $totalIterations += $response->iterations;
                $totalTokens += $response->tokensUsed;

                // Make this step's output available to subsequent steps
                $context[$step->agentName.'_output'] = $response->answer;
                $context['last_output'] = $response->answer;

                $this->renderer?->swarmStep($step->agentName, $step->status);

                // Stop on failure
                if ($step->status === 'failed') {
                    break;
                }
            } catch (\Throwable $e) {
                $step->status = 'failed';
                $this->renderer?->swarmStep($step->agentName, 'failed');
                $this->renderer?->error($e->getMessage());
                break;
            }
        }

        $durationMs = (microtime(true) - $start) * 1000;
        $finalOutput = end($this->steps)?->output() ?? '';
        $success = collect($this->steps)->every(fn ($s) => $s->isCompleted());

        return new SwarmResponse(
            steps: $this->steps,
            finalOutput: $finalOutput,
            totalIterations: $totalIterations,
            totalTokens: $totalTokens,
            totalDurationMs: $durationMs,
            success: $success,
        );
    }

    private function resolveAgent(string $agentName, string $role): \Laragent\Agent\AgentBuilder
    {
        $method = match (strtolower($agentName)) {
            'support' => 'support',
            'data' => 'data',
            'content' => 'content',
            'workflow' => 'workflow',
            'dev' => 'dev',
            'coding' => 'coding',
            'testing' => 'testing',
            'planning' => 'planning',
            'docs' => 'docs',
            'deploy' => 'deploy',
            'research' => 'research',
            'design' => 'design',
            'uiux' => 'uiux',
            default => null,
        };

        if ($method && method_exists($this->manager, $method)) {
            return $this->manager->$method()->builder();
        }

        // Fallback: custom agent with role as system prompt
        return $this->manager->make($agentName)
            ->system("You are a {$role} agent. ".$this->roleSystemPrompt($role));
    }

    private function roleSystemPrompt(string $role): string
    {
        return match (strtolower($role)) {
            'researcher' => 'Research thoroughly and provide detailed, accurate findings.',
            'planner' => 'Create detailed, actionable implementation plans with clear steps.',
            'coder' => 'Write complete, production-ready code following best practices.',
            'tester' => 'Write comprehensive tests covering happy paths, edge cases, and failures.',
            'designer' => 'Create clean, consistent design specifications and component definitions.',
            'uiux' => 'Design intuitive user interfaces focusing on clarity and user experience.',
            'documentation' => 'Write clear, comprehensive documentation developers will actually read.',
            'deployment' => 'Plan and execute deployments methodically with rollback strategies.',
            default => 'Complete your assigned task accurately and thoroughly.',
        };
    }
}
