<?php

namespace Laragent\Agent;

use Illuminate\Support\Facades\Event;
use Laragent\Events\AgentCompleted;
use Laragent\Events\AgentFailed;
use Laragent\Events\AgentStarted;
use Laragent\Events\AgentThinking;
use Laragent\Events\AgentToolCalled;
use Laragent\Events\AgentToolResult;
use Laragent\Models\AgentLog;
use Laragent\Models\AgentSession;
use Laragent\Providers\BaseProvider;
use Laragent\Tools\ToolRegistry;

class AgentRunner
{
    private array $toolCalls = [];

    public function __construct(
        private readonly BaseProvider $provider,
        private readonly ToolRegistry $tools,
        private readonly AgentMemory $memory,
        private readonly array $config = [],
    ) {}

    public function run(string $task, array $context = []): AgentResponse
    {
        $start = microtime(true);
        $session = $this->memory->getSession();
        $maxIterations = $this->config['max_iterations'] ?? config('laragent.max_iterations', 10);
        $enabledTools = $this->config['tools'] ?? [];
        $totalTokens = 0;
        $iterations = 0;

        try {
            // Update session to running
            $session->markAsRunning();
            Event::dispatch(new AgentStarted($session, $task));

            // Build system prompt
            $systemPrompt = $this->buildSystemPrompt($enabledTools, $context);

            // Initialize messages
            $messages = array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $this->memory->getMessages(),
                [['role' => 'user', 'content' => $task]]
            );

            $this->logStep($session, 'system', $systemPrompt);

            // Summarize memory if needed
            $this->memory->summarizeIfNeeded($this->provider);

            // Main reasoning loop
            while ($iterations < $maxIterations) {
                $iterations++;

                Event::dispatch(new AgentThinking($session, $iterations));

                // Call provider
                $providerResponse = $this->provider->complete($messages, [
                    'temperature' => $this->config['temperature'] ?? 0.7,
                    'model' => $this->config['model'] ?? null,
                ]);

                $totalTokens += $providerResponse->totalTokens();

                // Log the thinking step
                $this->logStep($session, 'think', $providerResponse->content, null, null, $providerResponse->totalTokens(), $providerResponse->durationMs);

                // Parse the response
                $parsed = $this->parseResponse($providerResponse->content);

                if ($parsed->isFinalAnswer()) {
                    // Add to memory
                    $this->memory->addMessage('user', $task);
                    $this->memory->addMessage('assistant', $parsed->content);

                    $this->logStep($session, 'final_answer', $parsed->content);

                    $durationMs = (microtime(true) - $start) * 1000;
                    $session->markAsCompleted($totalTokens, $iterations);

                    $response = new AgentResponse(
                        answer: $parsed->content,
                        sessionId: $session->id,
                        toolCalls: $this->toolCalls,
                        iterations: $iterations,
                        tokensUsed: $totalTokens,
                        durationMs: $durationMs,
                        success: true,
                    );

                    Event::dispatch(new AgentCompleted($session, $response));

                    return $response;
                }

                if ($parsed->isToolCall()) {
                    // Execute the tool
                    Event::dispatch(new AgentToolCalled($session, $parsed->toolName, $parsed->toolParams));

                    $toolResult = $this->executeTool($parsed->toolName, $parsed->toolParams, $session);

                    Event::dispatch(new AgentToolResult($session, $parsed->toolName, $toolResult));

                    // Add assistant message and tool result to messages
                    $messages[] = ['role' => 'assistant', 'content' => $providerResponse->content];
                    $messages[] = ['role' => 'user', 'content' => "Tool result for {$parsed->toolName}:\n{$toolResult}"];

                    continue;
                }

                // Fallback: not a final answer or tool call — add to messages and loop again
                $messages[] = ['role' => 'assistant', 'content' => $providerResponse->content];
                $messages[] = ['role' => 'user', 'content' => 'Please either use a tool or provide your final answer using <final_answer>your answer</final_answer>'];
            }

            // Max iterations reached
            $lastMessage = 'Agent reached maximum iterations without completing the task.';
            $durationMs = (microtime(true) - $start) * 1000;
            $session->markAsCompleted($totalTokens, $iterations);

            return new AgentResponse(
                answer: $lastMessage,
                sessionId: $session->id,
                toolCalls: $this->toolCalls,
                iterations: $iterations,
                tokensUsed: $totalTokens,
                durationMs: $durationMs,
                success: false,
                error: 'Max iterations reached',
            );
        } catch (\Throwable $e) {
            $durationMs = (microtime(true) - $start) * 1000;
            $session->markAsFailed($e->getMessage());
            $this->logStep($session, 'error', $e->getMessage());

            Event::dispatch(new AgentFailed($session, $e));

            return new AgentResponse(
                answer: 'Agent encountered an error: '.$e->getMessage(),
                sessionId: $session->id,
                toolCalls: $this->toolCalls,
                iterations: $iterations,
                tokensUsed: $totalTokens,
                durationMs: $durationMs,
                success: false,
                error: $e->getMessage(),
            );
        }
    }

    private function buildSystemPrompt(array $toolNames, array $context): string
    {
        $toolDescriptions = empty($toolNames)
            ? 'No tools available.'
            : $this->tools->descriptions($toolNames);

        $appContext = implode("\n", array_map(
            fn ($k, $v) => "{$k}: {$v}",
            array_keys($context),
            array_values($context)
        ));

        $customSystem = $this->config['system'] ?? '';

        $prompt = <<<PROMPT
You are a helpful AI assistant integrated into a Laravel web application.

You have access to the following tools:

{$toolDescriptions}

To use a tool, respond ONLY with:
<tool_call>
<n>{tool_name}</n>
<parameters>{valid_json_object}</parameters>
</tool_call>

When you have enough information to answer, respond ONLY with:
<final_answer>
{your_complete_response}
</final_answer>

Rules:
- Use tools when you need real data from the application
- Only use one tool per response
- After receiving a tool result, decide: use another tool or give final answer
- Be concise and practical — this is a production application
{$appContext}
PROMPT;

        if ($customSystem) {
            $prompt .= "\n\n".$customSystem;
        }

        return $prompt;
    }

    private function parseResponse(string $response): ParsedResponse
    {
        // Check for tool call (supports both <n> and <name> tags for compatibility)
        if (preg_match('/<tool_call>\s*(?:<n>|<name>)(.*?)(?:<\/n>|<\/name>)\s*<parameters>(.*?)<\/parameters>\s*<\/tool_call>/s', $response, $matches)) {
            $toolName = trim($matches[1]);
            $paramsJson = trim($matches[2]);
            $params = json_decode($paramsJson, true) ?? [];

            return new ParsedResponse(
                type: 'tool_call',
                content: $response,
                toolName: $toolName,
                toolParams: $params,
            );
        }

        // Check for final answer
        if (preg_match('/<final_answer>(.*?)<\/final_answer>/s', $response, $matches)) {
            return new ParsedResponse(
                type: 'final_answer',
                content: trim($matches[1]),
            );
        }

        // Fallback: unrecognized response — treat as thinking step, loop again
        return new ParsedResponse(
            type: 'thinking',
            content: $response,
        );
    }

    private function executeTool(string $name, array $params, AgentSession $session): string
    {
        $this->logStep($session, 'tool_call', "Calling {$name}", $name, $params);

        try {
            if (! $this->tools->has($name)) {
                $result = "ERROR: Tool '{$name}' not found.";
            } else {
                $tool = $this->tools->get($name);
                $result = $tool->execute($params);
            }

            $this->toolCalls[] = [
                'tool' => $name,
                'params' => $params,
                'result' => $result,
            ];

            $this->logStep($session, 'tool_result', $result, $name);

            return $result;
        } catch (\Throwable $e) {
            $error = "ERROR: Tool '{$name}' failed: ".$e->getMessage();
            $this->logStep($session, 'error', $error, $name);

            return $error;
        }
    }

    private function logStep(
        AgentSession $session,
        string $type,
        string $content,
        ?string $toolName = null,
        ?array $toolParams = null,
        ?int $tokensUsed = null,
        ?float $durationMs = null
    ): void {
        if (! config('laragent.log_steps', true)) {
            return;
        }

        AgentLog::create([
            'agent_session_id' => $session->id,
            'type' => $type,
            'content' => $content,
            'tool_name' => $toolName,
            'tool_parameters' => $toolParams,
            'tokens_used' => $tokensUsed,
            'duration_ms' => $durationMs,
        ]);
    }
}
