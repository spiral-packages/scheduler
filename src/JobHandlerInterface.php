<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

use Spiral\Scheduler\Job\Job;

interface JobHandlerInterface
{
    public function handle(Job $job): void;
}
