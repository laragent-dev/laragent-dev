<?php

namespace LaraAgent\Exceptions;

use InvalidArgumentException;

class InvalidProviderException extends InvalidArgumentException
{
    public static function forProvider(string $name): static
    {
        return new static(
            "{$name} is not a supported provider. Available: ollama, anthropic, openai"
        );
    }
}
