<?php

namespace LaraAgent\Facades;

use Illuminate\Support\Facades\Facade;
use LaraAgent\Agent\AgentBuilder;
use LaraAgent\Agent\AgentManager;
use LaraAgent\Agent\AgentPipeline;
use LaraAgent\Agent\AgentResponse;
use LaraAgent\Agents\ContentAgent;
use LaraAgent\Agents\DataAgent;
use LaraAgent\Agents\DevAgent;
use LaraAgent\Agents\SupportAgent;
use LaraAgent\Agents\WorkflowAgent;
use LaraAgent\Testing\AgentFake;

/**
 * @method static AgentBuilder make(?string $name = null)
 * @method static AgentResponse run(string $task)
 * @method static AgentBuilder tools(array $toolNames)
 * @method static AgentBuilder withMemory(?string $sessionId = null)
 * @method static AgentPipeline pipeline()
 * @method static SupportAgent support()
 * @method static DataAgent data()
 * @method static ContentAgent content()
 * @method static WorkflowAgent workflow()
 * @method static DevAgent dev()
 * @method static AgentFake fake()
 */
class Agent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laragent';
    }
}
