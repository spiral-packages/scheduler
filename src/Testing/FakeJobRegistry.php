<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Testing;

use PHPUnit\Framework\TestCase;
use Spiral\Scheduler\Job\Job;
use Spiral\Scheduler\JobRegistryInterface;

final class FakeJobRegistry implements JobRegistryInterface
{
    private array $registered = [];

    public function register(Job $job): void
    {
        $this->registered[] = $job;
    }

    public function assertRegistered(\Closure $callback): void
    {
        TestCase::assertTrue(
            \count($this->getRegisteredJobs($callback)) > 0,
            'The expected job was not dispatched.',
        );
    }

    public function assertRegisteredJob(Job $job): void
    {
        TestCase::assertContains(
            $job,
            $this->getJobs(),
            \sprintf('The expected [%s] job was not registered.', $job->getName()),
        );
    }

    public function assertNotRegisteredJob(Job $job): void
    {
        TestCase::assertNotContains(
            $job,
            $this->getJobs(),
            \sprintf('The expected [%s] job was registered.', $job->getName()),
        );
    }

    public function getDueJobs(\DateTimeInterface $date): iterable {}

    public function getJobs(): array
    {
        return $this->registered;
    }

    /**
     * @return array<Job>
     */
    private function getRegisteredJobs(\Closure $callback): array
    {
        $jobs = [];

        foreach ($this->registered as $job) {
            if ($callback($job)) {
                $jobs[] = $job;
            }
        }

        return $jobs;
    }
}
