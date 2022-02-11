<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Config;

use Spiral\Core\InjectableConfig;

final class SchedulerConfig extends InjectableConfig
{
    public const CONFIG = 'scheduler';

    protected $config = [
        'cacheStorage' => null,
        'queueConnection' => null,
        'timezone' => 'UTC',
        'expression' => [
            'aliases' => [],
        ],
    ];

    public function getTimezone(): \DateTimeZone
    {
        return new \DateTimeZone($this->config['timezone'] ?? 'UTC');
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
