<?php

namespace LaraAgent\Tools;

use Illuminate\Support\Facades\Http;

class HttpTool extends BaseTool
{
    // Private IP ranges to block (SSRF protection)
    private const BLOCKED_PATTERNS = [
        '/^127\./',
        '/^10\./',
        '/^192\.168\./',
        '/^172\.(1[6-9]|2[0-9]|3[0-1])\./',
        '/^169\.254\./',
        '/^::1$/',
        '/^localhost$/i',
    ];

    public function name(): string
    {
        return 'http_request';
    }

    public function description(): string
    {
        return 'Make HTTP requests to external APIs and web services.';
    }

    public function parameters(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'url'     => ['type' => 'string', 'description' => 'The URL to request'],
                'method'  => ['type' => 'string', 'enum' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']],
                'headers' => ['type' => 'object', 'description' => 'Request headers'],
                'body'    => ['type' => 'object', 'description' => 'Request body for POST/PUT'],
                'timeout' => ['type' => 'integer', 'description' => 'Timeout in seconds (max 30)'],
            ],
            'required'   => ['url', 'method'],
        ];
    }

    public function execute(array $params): string
    {
        $url = $params['url'] ?? '';
        $method = strtoupper($params['method'] ?? 'GET');
        $headers = $params['headers'] ?? [];
        $body = $params['body'] ?? null;
        $timeout = min((int) ($params['timeout'] ?? 30), 30);

        // Security: validate URL scheme
        $parsed = parse_url($url);
        if (!$parsed || !in_array($parsed['scheme'] ?? '', ['http', 'https'])) {
            return $this->error("Only http:// and https:// URLs are allowed. Got: {$url}");
        }

        // Security: block private IP ranges (SSRF protection)
        $host = $parsed['host'] ?? '';
        if ($this->isBlockedHost($host)) {
            return $this->error("Access to private/internal network addresses is not allowed: {$host}");
        }

        try {
            $request = Http::timeout($timeout)->withHeaders($headers);

            $response = match ($method) {
                'GET'    => $request->get($url),
                'POST'   => $request->post($url, $body ?? []),
                'PUT'    => $request->put($url, $body ?? []),
                'PATCH'  => $request->patch($url, $body ?? []),
                'DELETE' => $request->delete($url),
                default  => throw new \InvalidArgumentException("Unsupported method: {$method}"),
            };

            $body = $response->body();
            $truncated = strlen($body) > 2000;
            $output = substr($body, 0, 2000);

            return "Status: {$response->status()}\nBody:\n{$output}" .
                ($truncated ? "\n... (truncated at 2000 chars)" : '');
        } catch (\Exception $e) {
            return $this->error('HTTP request failed: ' . $e->getMessage());
        }
    }

    private function isBlockedHost(string $host): bool
    {
        foreach (self::BLOCKED_PATTERNS as $pattern) {
            if (preg_match($pattern, $host)) {
                return true;
            }
        }

        // Also resolve hostname and check the IP
        $ip = gethostbyname($host);
        if ($ip !== $host) {
            foreach (self::BLOCKED_PATTERNS as $pattern) {
                if (preg_match($pattern, $ip)) {
                    return true;
                }
            }
        }

        return false;
    }
}
