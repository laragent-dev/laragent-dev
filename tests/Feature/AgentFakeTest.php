<?php

use Laragent\Agent\AgentResponse;
use Laragent\Facades\Agent;
use Laragent\Testing\AgentFake;

beforeEach(function () {
    config(['laragent.memory_driver' => 'array']);
    config(['laragent.log_steps' => false]);
});

it('can fake agent responses', function () {
    $fake = Agent::fake();
    AgentFake::returns('Fake response: 42 users signed up');

    $result = Agent::run('How many users signed up?');

    expect($result)->toBeInstanceOf(AgentResponse::class);
    expect($result->answer)->toBe('Fake response: 42 users signed up');
});

it('asserts agent was run', function () {
    Agent::fake();
    AgentFake::returns('Done');

    Agent::run('Do something');

    AgentFake::assertRan();
});

it('asserts agent was run with specific task', function () {
    Agent::fake();
    AgentFake::returns('Done');

    Agent::run('Count all users in database');

    AgentFake::assertRanWith('users');
});

it('asserts tool was called', function () {
    Agent::fake();
    AgentFake::returns('Done');

    Agent::tools(['database_query'])->run('Count users');

    AgentFake::assertToolWasCalled('database_query');
});

it('asserts tool was not called', function () {
    Agent::fake();
    AgentFake::returns('Done');

    Agent::run('Simple question');

    AgentFake::assertToolNotCalled('send_email');
});

it('asserts run count', function () {
    Agent::fake();
    AgentFake::returns('Response 1');
    AgentFake::returns('Response 2');

    Agent::run('Task 1');
    Agent::run('Task 2');

    AgentFake::assertRunCount(2);
});

it('can be used in a real application test scenario', function () {
    // This demonstrates the primary use case: testing your app's use of agents
    // without actually calling any AI API

    Agent::fake();
    AgentFake::returns('We found 42 users who signed up this week.');

    // Simulate what your application code would do:
    $result = Agent::tools(['database_query'])
        ->run('How many users signed up this week?');

    // Assert the agent was used correctly
    AgentFake::assertRanWith('this week');
    AgentFake::assertToolWasCalled('database_query');

    // Assert the response is handled correctly
    expect($result->answer)->toContain('42 users');
    expect($result->wasSuccessful())->toBeTrue();
});
