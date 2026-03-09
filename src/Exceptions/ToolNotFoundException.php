<?php

namespace Laragent\Exceptions;

use InvalidArgumentException;

class ToolNotFoundException extends InvalidArgumentException
{
    public static function forTool(string $name, array $available): static
    {
        return new static(
            "Tool '{$name}' not found. Available tools: " . implode(', ', $available)
        );
    }
}
