<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests\Commands;

use Spiral\Scheduler\JobRegistryInterface;
use Spiral\Scheduler\Tests\TestCase;

final class ScheduleListCommandTest extends TestCase
{
    public function testEmptyList(): void
    {
        $registry = $this->mockContainer(JobRegistryInterface::class);
        $registry->shouldReceive('getJobs')->andReturn([]);

        $this->assertConsoleCommandOutputContainsStrings(
            'schedule:list',
            strings: ['No scheduled jobs registered.'],
        );
    }

    public function testRegisteredJobs(): void
    {
        $this->assertConsoleCommandOutputContainsStrings(
            'schedule:list',
            strings: ['Simple job', 'Another simple job'],
        );
    }
}
