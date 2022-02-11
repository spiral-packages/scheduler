<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Event;

use Spiral\Scheduler\Job\Job;

final class JobFinished
{
    public function __construct(
        public Job $job,
        public float $runtime
    ) {
    }
}
