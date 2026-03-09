<?php

namespace Laragent\Providers;

abstract class BaseProvider
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->validateConfig();
    }

    abstract public function complete(array $messages, array $options = []): ProviderResponse;

    abstract public function models(): array;

    abstract public function validateConfig(): void;

    public function getName(): string
    {
        $class = class_basename(static::class);

        return strtolower(str_replace('Provider', '', $class));
    }
}
