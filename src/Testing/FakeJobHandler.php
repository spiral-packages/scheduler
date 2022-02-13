<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Testing;

use PHPUnit\Framework\TestCase;
use Spiral\Scheduler\Job\Job;
use Spiral\Scheduler\JobHandlerInterface;

final class FakeJobHandler implements JobHandlerInterface
{
    private array $handled = [];

    public function assertHandled(\Closure $callback): void
    {
        TestCase::assertTrue(
            \count($this->getHandledJobs($callback)) > 0,
            'The expected job was not handled.'
        );
    }

    public function assertHandledTotalJobs(int $totalJobs): void
    {
        $count = \count($this->handled);
        TestCase::assertTrue(
            $count === $totalJobs,
            \sprintf('Scheduler handled {%d} jobs instead of {%d} times.', $count, $totalJobs)
        );
    }

    public function assertHandledJob(Job $job): void
    {
        TestCase::assertContains(
            $job,
            $this->handled,
            \sprintf('The expected job [%s] was not handled.', $job->getId())
        );
    }

    public function assertNotHandledJob(Job $job): void
    {
        TestCase::assertNotContains(
            $job,
            $this->handled,
            \sprintf('The expected job [%s] was handled unexpectedly.', $job->getId())
        );
    }

    public function handle(Job $job): void
    {
        $this->handled[] = $job;
    }

    private function getHandledJobs(\Closure $callback): array
    {
        $jobs = [];

        foreach ($this->handled as $job) {
            if ($callback($job)) {
                $jobs[] = $job;
            }
        }

        return $jobs;
    }
}
