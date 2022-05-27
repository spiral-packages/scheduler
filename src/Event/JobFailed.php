<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Event;

use Spiral\Scheduler\Job\Job;

final class JobFailed
{
    public function __construct(
        public readonly Job $job,
        public readonly \Throwable $exception
    ) {
    }
}
