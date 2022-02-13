<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests\Job;

use Cron\CronExpression;
use Mockery as m;
use Spiral\Scheduler\CommandBuilder;
use Spiral\Scheduler\CommandRunner;
use Spiral\Scheduler\Job\CommandJob;
use Spiral\Scheduler\Mutex\JobMutexInterface;
use Spiral\Scheduler\ProcessFactory;
use Spiral\Scheduler\Tests\TestCase;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

final class CommandJobTest extends TestCase
{
    private CommandJob $job;
    private m\MockInterface $processFactory;
    private m\MockInterface $mutex;

    protected function setUp(): void
    {
        parent::setUp();

        $this->job = new CommandJob(
            new CommandBuilder(new CommandRunner(
                $finder = m::mock(PhpExecutableFinder::class)
            )),
            $this->processFactory = m::mock(ProcessFactory::class),
            $this->mutex = m::mock(JobMutexInterface::class),
            new CronExpression('* * * * *'),
            'foo:bar'
        );

        $finder->shouldReceive('find')->andReturn('/usr/bin/php');
    }

    public function testGetsName(): void
    {
        $this->assertSame('foo:bar', $this->job->getName());
    }

    public function testGetsExpression(): void
    {
        $this->assertSame('*/5 * * * *', $this->job->everyFiveMinutes()->getExpression());
    }

    public function testGetsSetsDescription(): void
    {
        $this->assertNull($this->job->getDescription());

        $this->job->description($desc = 'Simple description');
        $this->assertSame($desc, $this->job->getDescription());
    }

    public function testRun(): void
    {
        $this->processFactory->shouldReceive('createFromShellCommandline')
            ->with('foo:bar > \'/dev/null\' 2>&1')
            ->andReturn($process = m::mock(Process::class));

        $process->shouldReceive('run')->once()->andReturn(1);

        $this->job->run($this->getContainer());
    }
}
