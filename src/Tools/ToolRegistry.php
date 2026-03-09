<?php

namespace Laragent\Tools;

use Laragent\Exceptions\ToolNotFoundException;

class ToolRegistry
{
    /** @var array<string, BaseTool> */
    private array $tools = [];

    /**
     * Short aliases for built-in tools.
     * Allows builders to use 'database' instead of 'database_query', etc.
     */
    private const ALIASES = [
        'database'   => 'database_query',
        'mailer'     => 'send_email',
        'mail'       => 'send_email',
        'http'       => 'http_request',
        'artisan'    => 'run_artisan',
    ];

    public function register(BaseTool $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    public function get(string $name): BaseTool
    {
        $resolved = $this->resolve($name);

        if (!isset($this->tools[$resolved])) {
            throw new ToolNotFoundException(
                "Tool '{$name}' not found. Available tools: " . implode(', ', array_keys($this->tools))
            );
        }

        return $this->tools[$resolved];
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$this->resolve($name)]);
    }

    public function all(): array
    {
        return $this->tools;
    }

    public function only(array $names): array
    {
        $result = [];
        foreach ($names as $name) {
            $resolved = $this->resolve($name);
            if (isset($this->tools[$resolved])) {
                $result[$resolved] = $this->tools[$resolved];
            }
        }
        return $result;
    }

    /**
     * Build tool descriptions string for injection into the system prompt.
     */
    public function schemas(array $names = []): string
    {
        $tools = empty($names) ? $this->tools : $this->only($names);

        if (empty($tools)) {
            return 'No tools available.';
        }

        $lines = [];
        foreach ($tools as $tool) {
            $params = json_encode($tool->parameters(), JSON_PRETTY_PRINT);
            $lines[] = "Tool: {$tool->name()}\nDescription: {$tool->description()}\nParameters: {$params}";
        }

        return implode("\n\n", $lines);
    }

    /**
     * @deprecated Use schemas() instead
     */
    public function descriptions(array $names = []): string
    {
        return $this->schemas($names);
    }

    private function resolve(string $name): string
    {
        return self::ALIASES[$name] ?? $name;
    }
}
