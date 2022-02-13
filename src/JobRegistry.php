<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

use Spiral\Scheduler\Job\Job;

final class JobRegistry implements JobRegistryInterface
{
    /** @var array<Job> */
    private array $jobs = [];

    public function register(Job $job): void
    {
        $this->jobs[] = $job;
    }

    public function getDueJobs(\DateTimeInterface $date): iterable
    {
        foreach ($this->jobs as $job) {
            if (! $job->isDue($date)) {
                continue;
            }

            yield $job;
        }
    }

    public function getJobs(): array
    {
        return $this->jobs;
    }
}
