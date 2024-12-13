<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

interface PeriodicCommandRunnerInterface
{
    public function run(string $command, ?\Closure $onSuccess = null, ?\Closure $onError = null): void;
}
