<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests;

use Mockery as m;
use Spiral\Scheduler\Job\Job;
use Spiral\Scheduler\JobRegistry;

final class JobRegistryTest extends TestCase
{
    private JobRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new JobRegistry();
    }

    public function testJobShouldBeRegistered(): void
    {
        $this->assertCount(0, $this->registry->getJobs());
        $this->registry->register(m::mock(Job::class));
        $this->assertCount(1, $this->registry->getJobs());
    }

    public function testGetsGueJobs()
    {
        $date = new \DateTimeImmutable();

        $this->registry->register($job1 = m::mock(Job::class));
        $job1->shouldReceive('isDue')->once()->with($date)->andReturnTrue();

        $this->registry->register($job2 = m::mock(Job::class));
        $job2->shouldReceive('isDue')->once()->with($date)->andReturnFalse();

        $this->registry->register($job3 = m::mock(Job::class));
        $job3->shouldReceive('isDue')->once()->with($date)->andReturnTrue();

        $jobs = iterator_to_array($this->registry->getDueJobs($date));

        $this->assertCount(2, $jobs);
        $this->assertContains($job1, $jobs);
        $this->assertContains($job3, $jobs);
    }
}
