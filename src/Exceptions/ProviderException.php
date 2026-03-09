<?php

namespace LaraAgent\Exceptions;

use RuntimeException;

class ProviderException extends RuntimeException
{
    public static function connectionRefused(string $hint): static
    {
        return new static($hint);
    }

    public static function modelNotFound(string $model): static
    {
        return new static(
            "Model '{$model}' not found locally. Pull it with: ollama pull {$model}"
        );
    }

    public static function invalidApiKey(string $provider): static
    {
        return new static(
            "Invalid {$provider} API key. Check " . strtoupper($provider) . "_API_KEY in your .env"
        );
    }

    public static function notConfigured(string $provider, string $key): static
    {
        return new static(
            "{$provider} API key not set. Add {$key} to your .env file"
        );
    }
}
