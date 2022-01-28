<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

final class CommandUtils
{
    /**
     * Compile parameters for a command.
     */
    public static function compileParameters(array $parameters): string
    {
        $string = '';

        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                return static::compileArrayInput($key, $value);
            }

            if (! is_numeric($value) && ! preg_match('/^(-.$|--.*)/i', $value)) {
                $value = ProcessUtils::escapeArgument($value);
            }

            $string .= is_numeric($key) ? $value : "{$key}={$value}";
        }

        return $string;
    }

    /**
     * Compile array input for a command.
     */
    public static function compileArrayInput(string|int $key, array $value): string
    {
        $value = array_map(static function ($value): string {
            return ProcessUtils::escapeArgument($value);
        }, $value);

        if (str_starts_with($key, '--')) {
            $value = array_map(static function ($value) use ($key): string {
                return "{$key}={$value}";
            }, $value);
        } elseif (str_starts_with($key, '-')) {
            $value = array_map(static function ($value) use ($key): string {
                return "{$key} {$value}";
            }, $value);
        }

        return implode(' ', $value);
    }
}
