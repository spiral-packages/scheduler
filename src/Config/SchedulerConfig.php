<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Config;

use Spiral\Core\InjectableConfig;

final class SchedulerConfig extends InjectableConfig
{
    public const CONFIG = 'scheduler';

    protected array $config = [
        'cacheStorage' => null,
        'queueConnection' => null,
        'timezone' => 'UTC',
        'expression' => [
            'aliases' => [],
        ],
    ];

    public function getTimezone(): ?\DateTimeZone
    {
        $timezone = $this->config['timezone'] ?? null;
        if ($timezone === null) {
            return null;
        }

        return new \DateTimeZone($timezone);
    }

    public function getExpressionAliases(): ?array
    {
        return $this->config['expression']['aliases'] ?? null;
    }

    public function getCacheStorage(): ?string
    {
        return $this->config['cacheStorage'] ?? null;
    }

    public function getQueueConnection(): ?string
    {
        return $this->config['queueConnection'] ?? null;
    }
}
