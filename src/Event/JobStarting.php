<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Event;

use Spiral\Scheduler\Job\Job;

final class JobStarting
{
    public function __construct(
        public readonly Job $job
    ) {
    }
}
