<?php

namespace Laragent\Testing;

use Illuminate\Support\Str;
use Laragent\Agent\AgentBuilder;
use Laragent\Agent\AgentManager;
use Laragent\Agent\AgentResponse;
use Laragent\Tools\ToolRegistry;
use PHPUnit\Framework\Assert;

class AgentFake extends AgentManager
{
    private static array $responses = [];

    private static array $calls = [];

    private static int $responseIndex = 0;

    public function __construct(ToolRegistry $toolRegistry)
    {
        parent::__construct($toolRegistry);
    }

    public static function returns(string $answer): static
    {
        static::$responses[] = $answer;

        return app('laragent');
    }

    public function make(?string $name = null): AgentBuilder
    {
        return new FakeAgentBuilder(
            toolRegistry: $this->getToolRegistry(),
            name: $name,
            fake: $this,
        );
    }

    public function run(string $task): AgentResponse
    {
        return $this->make()->run($task);
    }

    public function tools(array $toolNames): AgentBuilder
    {
        return $this->make()->tools($toolNames);
    }

    public function recordCall(string $task, array $toolNames): AgentResponse
    {
        $answer = static::$responses[static::$responseIndex] ?? 'Fake agent response';
        static::$responseIndex = min(static::$responseIndex + 1, count(static::$responses) - 1);

        static::$calls[] = [
            'task' => $task,
            'tools' => $toolNames,
        ];

        return new AgentResponse(
            answer: $answer,
            sessionId: Str::uuid()->toString(),
            toolCalls: [],
            iterations: 1,
            tokensUsed: 10,
            durationMs: 1.0,
            success: true,
        );
    }

    public static function assertRan(): void
    {
        Assert::assertNotEmpty(static::$calls, 'Expected agent to have been run, but it was not.');
    }

    public static function assertRanWith(string $taskContaining): void
    {
        $found = collect(static::$calls)->contains(
            fn ($call) => str_contains($call['task'], $taskContaining)
        );

        Assert::assertTrue($found, "Expected agent to be run with task containing '{$taskContaining}'.");
    }

    public static function assertToolWasCalled(string $tool): void
    {
        $found = collect(static::$calls)->contains(
            fn ($call) => in_array($tool, $call['tools'])
        );

        Assert::assertTrue($found, "Expected tool '{$tool}' to have been used.");
    }

    public static function assertToolNotCalled(string $tool): void
    {
        $found = collect(static::$calls)->contains(
            fn ($call) => in_array($tool, $call['tools'])
        );

        Assert::assertFalse($found, "Expected tool '{$tool}' to NOT have been used.");
    }

    public static function assertCompleted(): void
    {
        Assert::assertNotEmpty(static::$calls, 'Expected agent to have completed a run.');
    }

    public static function assertFailed(): void
    {
        Assert::fail('AgentFake always succeeds. Use custom responses to test failure scenarios.');
    }

    public static function assertRunCount(int $count): void
    {
        Assert::assertCount($count, static::$calls, "Expected agent to have run {$count} time(s).");
    }

    public static function assertNotRan(): void
    {
        Assert::assertEmpty(static::$calls, 'Expected agent to NOT have been run, but it was.');
    }

    public static function whenTaskContains(string $keyword, string $answer): static
    {
        // Store as conditional response — checked in recordCall
        static::$responses[] = ['keyword' => $keyword, 'answer' => $answer];

        return app('laragent');
    }

    public static function reset(): void
    {
        static::$responses = [];
        static::$calls = [];
        static::$responseIndex = 0;
    }
}

class FakeAgentBuilder extends AgentBuilder
{
    private AgentFake $fake;

    private array $toolNamesForFake = [];

    public function __construct(ToolRegistry $toolRegistry, ?string $name, AgentFake $fake)
    {
        parent::__construct($toolRegistry, $name);
        $this->fake = $fake;
    }

    public function tools(array $toolNames): static
    {
        $this->toolNamesForFake = $toolNames;

        return parent::tools($toolNames);
    }

    public function run(string $task): AgentResponse
    {
        return $this->fake->recordCall($task, $this->toolNamesForFake);
    }
}
