<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Config;

use Spiral\Core\InjectableConfig;

final class SchedulerConfig extends InjectableConfig
{
    public const CONFIG = 'scheduler';

    protected $config = [
        'cacheStorage' => null,
        'queueConnection' => null
    ];

    public function getCacheStorage(): ?string
    {
        return $this->config['cacheStorage'] ?? null;
    }

    public function getQueueConnection(): ?string
    {
        return $this->config['queueConnection'] ?? null;
    }
}
