<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

use Spiral\Scheduler\Job\Job;

interface JobRegistryInterface
{
    public function register(Job $job): void;

    /**
     * @param \DateTimeInterface $date
     * @return iterable<Job>
     */
    public function getDueJobs(\DateTimeInterface $date): iterable;

    /**
     * @return array<Job>
     */
    public function getJobs(): array;
}
