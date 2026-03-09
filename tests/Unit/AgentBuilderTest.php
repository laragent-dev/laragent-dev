<?php

use Laragent\Agent\AgentBuilder;
use Laragent\Tools\ToolRegistry;
use InvalidArgumentException;

beforeEach(function () {
    $this->registry = app(ToolRegistry::class);
});

it('creates an agent builder with a name', function () {
    $builder = new AgentBuilder($this->registry, 'test-agent');
    expect($builder)->toBeInstanceOf(AgentBuilder::class);
});

it('sets provider correctly', function () {
    $builder = new AgentBuilder($this->registry);
    $result = $builder->provider('anthropic');
    expect($result)->toBeInstanceOf(AgentBuilder::class);
});

it('sets tools correctly', function () {
    $builder = new AgentBuilder($this->registry);
    $result = $builder->tools(['database_query']);
    expect($result)->toBeInstanceOf(AgentBuilder::class);
});

it('sets temperature within valid range', function () {
    $builder = new AgentBuilder($this->registry);
    $result = $builder->temperature(0.5);
    expect($result)->toBeInstanceOf(AgentBuilder::class);
});

it('throws exception for temperature below 0', function () {
    $builder = new AgentBuilder($this->registry);
    expect(fn() => $builder->temperature(-0.1))->toThrow(InvalidArgumentException::class);
});

it('throws exception for temperature above 1', function () {
    $builder = new AgentBuilder($this->registry);
    expect(fn() => $builder->temperature(1.1))->toThrow(InvalidArgumentException::class);
});

it('sets async mode', function () {
    $builder = new AgentBuilder($this->registry);
    $result = $builder->async();
    expect($result)->toBeInstanceOf(AgentBuilder::class);
});

it('sets max iterations', function () {
    $builder = new AgentBuilder($this->registry);
    $result = $builder->maxIterations(5);
    expect($result)->toBeInstanceOf(AgentBuilder::class);
});

it('accepts context data', function () {
    $builder = new AgentBuilder($this->registry);
    $result = $builder->context(['key' => 'value']);
    expect($result)->toBeInstanceOf(AgentBuilder::class);
});

it('enables memory with session id', function () {
    $builder = new AgentBuilder($this->registry);
    $result = $builder->withMemory('session-123');
    expect($result)->toBeInstanceOf(AgentBuilder::class);
});
