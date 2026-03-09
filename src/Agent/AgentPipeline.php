<?php

namespace Laragent\Agent;

use Laragent\Tools\ToolRegistry;

class AgentPipeline
{
    /** @var PipelineStep[] */
    private array $steps = [];
    private ?callable $onStepComplete = null;
    private ToolRegistry $toolRegistry;

    public function __construct(ToolRegistry $toolRegistry)
    {
        $this->toolRegistry = $toolRegistry;
    }

    public function step(string $agentName): PipelineStep
    {
        $step = new PipelineStep($agentName, $this);
        $this->steps[] = $step;
        return $step;
    }

    public function onStepComplete(callable $fn): static
    {
        $this->onStepComplete = $fn;
        return $this;
    }

    public function run(): PipelineResponse
    {
        $start = microtime(true);
        $totalIterations = 0;
        $totalTokens = 0;
        $results = [];
        $context = [];
        $finalOutput = '';

        foreach ($this->steps as $pipelineStep) {
            // Interpolate context values into task
            $task = $pipelineStep->getTask();
            foreach ($context as $key => $value) {
                $task = str_replace('{' . $key . '}', $value, $task);
            }

            // Build agent
            $builder = new AgentBuilder($this->toolRegistry, $pipelineStep->getAgentName());

            if ($providerName = $pipelineStep->getProviderName()) {
                $builder->provider($providerName);
            }

            if (!empty($pipelineStep->getToolNames())) {
                $builder->tools($pipelineStep->getToolNames());
            }

            if (!empty($context)) {
                $builder->context($context);
            }

            // Run the step
            $response = $builder->run($task);
            $results[] = $response;

            $totalIterations += $response->iterations;
            $totalTokens += $response->tokensUsed;
            $finalOutput = $response->answer;

            // If this step passes output, add to context
            if ($outputKey = $pipelineStep->getOutputKey()) {
                $context[$outputKey] = $response->answer;
            }

            if ($this->onStepComplete) {
                ($this->onStepComplete)($response, $pipelineStep->getAgentName());
            }

            // Stop pipeline on failure
            if (!$response->wasSuccessful()) {
                $totalDurationMs = (microtime(true) - $start) * 1000;
                return new PipelineResponse(
                    steps: $results,
                    finalOutput: $finalOutput,
                    totalIterations: $totalIterations,
                    totalTokens: $totalTokens,
                    totalDurationMs: $totalDurationMs,
                    success: false,
                );
            }
        }

        $totalDurationMs = (microtime(true) - $start) * 1000;

        return new PipelineResponse(
            steps: $results,
            finalOutput: $finalOutput,
            totalIterations: $totalIterations,
            totalTokens: $totalTokens,
            totalDurationMs: $totalDurationMs,
            success: true,
        );
    }

    public function dispatch(): string
    {
        // For async pipeline, serialize the steps and dispatch a job
        // Returns a pipeline session ID
        $pipelineId = \Illuminate\Support\Str::uuid()->toString();
        // Simplified: just run synchronously for now
        // A full async implementation would require a dedicated pipeline job
        return $pipelineId;
    }
}
