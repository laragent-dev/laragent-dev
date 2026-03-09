<?php

namespace Laragent\Providers;

use Laragent\Exceptions\InvalidProviderException;

class ProviderFactory
{
    public static function make(string $name, array $config): BaseProvider
    {
        return match ($name) {
            'ollama' => new OllamaProvider($config),
            'anthropic' => new AnthropicProvider($config),
            'openai' => new OpenAIProvider($config),
            default => throw InvalidProviderException::forProvider($name),
        };
    }
}
