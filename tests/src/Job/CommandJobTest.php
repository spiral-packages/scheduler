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

    public function testGetsId(): void
    {
        $this->assertSame('schedule-6a31f94cad6c051f7fc2c0bcef19cbcded3f7330', $this->job->getId());
    }

    public function testGetsSystemDescription(): void
    {
        $this->assertSame('foo:bar > \'/dev/null\' 2>&1', $this->job->getSystemDescription());
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

    public function testRunInBackground(): void
    {
        $this->processFactory->shouldReceive('createFromShellCommandline')
            ->with('(foo:bar > \'/dev/null\' 2>&1 ; /usr/bin/php app.php schedule:finish "schedule-6a31f94cad6c051f7fc2c0bcef19cbcded3f7330" "$?") > \'/dev/null\' 2>&1 &')
            ->andReturn($process = m::mock(Process::class));

        $process->shouldReceive('run')->once()->andReturn(1);

        $this->job->runInBackground()->run($this->getContainer());
    }

    public function testRunWithoutOverlapping(): void
    {
        $this->mutex->shouldReceive('create')
            ->once()
            ->with('schedule-6a31f94cad6c051f7fc2c0bcef19cbcded3f7330', 1440)
            ->andReturnTrue();


        $this->mutex->shouldReceive('forget')
            ->once()
            ->with('schedule-6a31f94cad6c051f7fc2c0bcef19cbcded3f7330');

        $this->processFactory->shouldReceive('createFromShellCommandline')
            ->with('foo:bar > \'/dev/null\' 2>&1')
            ->andReturn($process = m::mock(Process::class));

        $process->shouldReceive('run')->once()->andReturn(1);

        $this->job->withoutOverlapping()->run($this->getContainer());
    }

    public function testRunWithoutOverlappingWithFiredJob(): void
    {
        $this->mutex->shouldReceive('create')
            ->once()
            ->with('schedule-6a31f94cad6c051f7fc2c0bcef19cbcded3f7330', 1600)
            ->andReturnFalse();

        $this->job->withoutOverlapping(1600)->run($this->getContainer());

        $this->assertSame(1600, $this->job->getExpiresAt());
    }
}
