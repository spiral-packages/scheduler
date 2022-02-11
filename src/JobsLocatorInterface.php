<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

use Spiral\Scheduler\Job\CallbackJob;

interface JobsLocatorInterface
{
    /**
     * @return CallbackJob[]
     */
    public function getJobs(): array;
}
