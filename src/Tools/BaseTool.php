<?php

namespace LaraAgent\Tools;

abstract class BaseTool
{
    abstract public function name(): string;

    abstract public function description(): string;

    abstract public function parameters(): array;

    abstract public function execute(array $params): string;

    protected function error(string $message): string
    {
        return "ERROR: {$message}";
    }

    public function toArray(): array
    {
        return [
            'name'        => $this->name(),
            'description' => $this->description(),
            'parameters'  => $this->parameters(),
        ];
    }
}
