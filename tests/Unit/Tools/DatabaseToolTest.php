<?php

use Laragent\Tools\DatabaseTool;

beforeEach(function () {
    config(['laragent.allowed_models' => []]);
});

it('returns error for unknown model', function () {
    $tool = new DatabaseTool();
    $result = $tool->execute(['model' => 'NonExistentModel12345', 'action' => 'count']);
    expect($result)->toContain('ERROR');
});

it('returns error for disallowed model when allowlist is set', function () {
    config(['laragent.allowed_models' => ['User']]);
    $tool = new DatabaseTool();
    $result = $tool->execute(['model' => 'Order', 'action' => 'count']);
    expect($result)->toContain('ERROR');
});

it('has correct tool name', function () {
    $tool = new DatabaseTool();
    expect($tool->name())->toBe('database_query');
});

it('has description', function () {
    $tool = new DatabaseTool();
    expect($tool->description())->not->toBeEmpty();
});

it('has valid parameters schema', function () {
    $tool = new DatabaseTool();
    $params = $tool->parameters();
    expect($params)->toHaveKey('type');
    expect($params)->toHaveKey('properties');
    expect($params['properties'])->toHaveKey('model');
    expect($params['properties'])->toHaveKey('action');
});
