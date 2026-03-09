<?php

use Illuminate\Support\Facades\Storage;
use Laragent\Tools\FilesystemTool;

beforeEach(function () {
    Storage::fake('local');
    config(['laragent.sandbox_path' => 'agent-sandbox']);
});

it('blocks path traversal with ..', function () {
    $tool = new FilesystemTool;
    $result = $tool->execute(['action' => 'read', 'path' => '../etc/passwd']);
    expect($result)->toContain('ERROR');
});

it('blocks absolute paths', function () {
    $tool = new FilesystemTool;
    $result = $tool->execute(['action' => 'read', 'path' => '/etc/passwd']);
    expect($result)->toContain('ERROR');
});

it('can write and read a file', function () {
    $tool = new FilesystemTool;

    $writeResult = $tool->execute([
        'action' => 'write',
        'path' => 'test.txt',
        'content' => 'Hello, World!',
    ]);

    expect($writeResult)->toContain('written');

    $readResult = $tool->execute([
        'action' => 'read',
        'path' => 'test.txt',
    ]);

    expect($readResult)->toBe('Hello, World!');
});

it('returns correct list of files', function () {
    $tool = new FilesystemTool;

    $tool->execute(['action' => 'write', 'path' => 'file1.txt', 'content' => 'a']);
    $tool->execute(['action' => 'write', 'path' => 'file2.txt', 'content' => 'b']);

    $result = $tool->execute(['action' => 'list', 'path' => '']);
    $files = json_decode($result, true);

    expect($files)->toContain('file1.txt');
    expect($files)->toContain('file2.txt');
});

it('checks file existence correctly', function () {
    $tool = new FilesystemTool;

    $result = $tool->execute(['action' => 'exists', 'path' => 'nonexistent.txt']);
    expect($result)->toContain('false');

    $tool->execute(['action' => 'write', 'path' => 'exists.txt', 'content' => 'hi']);
    $result = $tool->execute(['action' => 'exists', 'path' => 'exists.txt']);
    expect($result)->toContain('true');
});
