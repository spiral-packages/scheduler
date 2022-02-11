<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Event;

use Spiral\Scheduler\Job\Job;

final class BackgroundJobFinished
{
    public function __construct(
        public Job $job
    ) {
    }
}
