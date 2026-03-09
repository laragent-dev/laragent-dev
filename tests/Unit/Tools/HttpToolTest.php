<?php

use Illuminate\Support\Facades\Http;
use LaraAgent\Tools\HttpTool;

it('blocks localhost (127.0.0.1)', function () {
    $tool = new HttpTool();
    $result = $tool->execute(['url' => 'http://127.0.0.1/secret', 'method' => 'GET']);
    expect($result)->toContain('ERROR');
    expect($result)->toContain('private');
});

it('blocks private IP 192.168.x.x', function () {
    $tool = new HttpTool();
    $result = $tool->execute(['url' => 'http://192.168.1.1/admin', 'method' => 'GET']);
    expect($result)->toContain('ERROR');
});

it('blocks private IP 10.x.x.x', function () {
    $tool = new HttpTool();
    $result = $tool->execute(['url' => 'http://10.0.0.1/', 'method' => 'GET']);
    expect($result)->toContain('ERROR');
});

it('blocks localhost hostname', function () {
    $tool = new HttpTool();
    $result = $tool->execute(['url' => 'http://localhost/admin', 'method' => 'GET']);
    expect($result)->toContain('ERROR');
});

it('blocks file:// scheme', function () {
    $tool = new HttpTool();
    $result = $tool->execute(['url' => 'file:///etc/passwd', 'method' => 'GET']);
    expect($result)->toContain('ERROR');
});

it('blocks ftp:// scheme', function () {
    $tool = new HttpTool();
    $result = $tool->execute(['url' => 'ftp://files.example.com/file', 'method' => 'GET']);
    expect($result)->toContain('ERROR');
});

it('allows external URLs', function () {
    Http::fake([
        'api.example.com/*' => Http::response(['data' => 'test'], 200),
    ]);

    $tool = new HttpTool();
    $result = $tool->execute(['url' => 'https://api.example.com/data', 'method' => 'GET']);
    expect($result)->toContain('200');
});
