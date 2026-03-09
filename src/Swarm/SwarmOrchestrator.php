<?php

namespace Laragent\Swarm;

use Laragent\Agent\AgentManager;

/**
 * Analyzes a complex task and builds a SwarmStep plan automatically.
 *
 * The orchestrator uses an AI agent to decompose the task, then
 * routes each subtask to the appropriate specialist agent.
 */
class SwarmOrchestrator
{
    // Predefined swarm templates for common Laravel development tasks
    private const SWARM_TEMPLATES = [
        'feature' => [
            ['planning',      'planner',       'Create an implementation plan for: {task}', ['filesystem']],
            ['coding',        'coder',          'Implement the feature based on this plan: {planning_output}', ['filesystem', 'artisan']],
            ['testing',       'tester',         'Write tests for the implementation described in: {planning_output}', ['filesystem']],
            ['documentation', 'documentation',  'Document the feature and API: {planning_output}', ['filesystem']],
        ],
        'api' => [
            ['planning',      'planner',       'Plan a REST API for: {task}', ['filesystem']],
            ['coding',        'coder',          'Generate the API controllers and routes: {planning_output}', ['filesystem', 'artisan']],
            ['testing',       'tester',         'Write feature tests for the API endpoints: {planning_output}', ['filesystem']],
            ['documentation', 'documentation',  'Write API documentation: {planning_output}', ['filesystem']],
        ],
        'frontend' => [
            ['planning',      'planner',        'Plan the frontend component for: {task}', ['filesystem']],
            ['uiux',          'uiux',           'Design the UI/UX: {planning_output}', ['filesystem']],
            ['coding',        'coder',          'Implement the frontend components: {uiux_output}', ['filesystem']],
            ['testing',       'tester',         'Write browser and unit tests: {planning_output}', ['filesystem']],
        ],
        'audit' => [
            ['research',     'researcher',      'Audit the codebase for issues: {task}', ['filesystem', 'database']],
            ['documentation', 'documentation',  'Write an audit report: {research_output}', ['filesystem']],
        ],
    ];

    public function __construct(
        private readonly AgentManager $manager,
    ) {}

    /**
     * Auto-build a swarm from a natural language task description.
     * Uses the AI to decide which agents are needed.
     */
    public function plan(string $task, string $template = 'feature'): AgentSwarm
    {
        $swarm = new AgentSwarm($this->manager);
        $template = self::SWARM_TEMPLATES[$template] ?? self::SWARM_TEMPLATES['feature'];

        foreach ($template as [$agentName, $role, $taskTemplate, $tools]) {
            // Replace {task} placeholder with the actual task
            $stepTask = str_replace('{task}', $task, $taskTemplate);

            $swarm->addStep(new SwarmStep(
                agentName: $agentName,
                role: $role,
                task: $stepTask,
                tools: $tools,
            ));
        }

        return $swarm->withContext(['task' => $task]);
    }

    /**
     * Get available swarm templates.
     */
    public static function templates(): array
    {
        return array_keys(self::SWARM_TEMPLATES);
    }
}
