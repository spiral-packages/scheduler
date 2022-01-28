<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Config;

use Spiral\Core\InjectableConfig;

final class SchedulerConfig extends InjectableConfig
{
    public const CONFIG = 'scheduler';
    protected $config = [];
}
