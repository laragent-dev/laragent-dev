<?php

namespace Laragent\Facades;

use Illuminate\Support\Facades\Facade;
use Laragent\Agent\AgentBuilder;
use Laragent\Agent\AgentPipeline;
use Laragent\Agent\AgentResponse;
use Laragent\Agents\CodingAgent;
use Laragent\Agents\ContentAgent;
use Laragent\Agents\DataAgent;
use Laragent\Agents\DeploymentAgent;
use Laragent\Agents\DesignAgent;
use Laragent\Agents\DevAgent;
use Laragent\Agents\DocumentationAgent;
use Laragent\Agents\PlanningAgent;
use Laragent\Agents\ResearchAgent;
use Laragent\Agents\SupportAgent;
use Laragent\Agents\TestingAgent;
use Laragent\Agents\UiUxAgent;
use Laragent\Agents\WorkflowAgent;
use Laragent\Testing\AgentFake;

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
 * @method static CodingAgent coding()
 * @method static TestingAgent testing()
 * @method static PlanningAgent planning()
 * @method static DocumentationAgent docs()
 * @method static DeploymentAgent deploy()
 * @method static ResearchAgent research()
 * @method static DesignAgent design()
 * @method static UiUxAgent uiux()
 * @method static AgentFake fake()
 */
class Agent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laragent';
    }
}
