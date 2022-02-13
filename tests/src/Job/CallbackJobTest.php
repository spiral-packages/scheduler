<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests\Job;

use Cron\CronExpression;
use Spiral\Queue\QueueConnectionProviderInterface;
use Spiral\Queue\QueueInterface;
use Spiral\Scheduler\Job\CallbackJob;
use Spiral\Scheduler\Mutex\JobMutexInterface;
use Mockery as m;
use Spiral\Scheduler\Tests\App\PingerInterface;
use Spiral\Scheduler\Tests\TestCase;

final class CallbackJobTest extends TestCase
{
    private \Mockery\MockInterface $pinger;
    private CallbackJob $job;
    private m\MockInterface $mutex;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pinger = $this->mockContainer(PingerInterface::class);

        $this->job = new CallbackJob(
            $this->mutex = $this->mockContainer(JobMutexInterface::class),
            new CronExpression('* * * * *'),
            'Callable job description',
            function (PingerInterface $pinger, string $url) {
                $pinger->ping($url);
            },
            [
                'url' => 'https://site.com',
            ]
        );
    }

    public function testGetsId(): void
    {
        $this->assertSame('schedule-64ec6d2de2e52435fdb2cb0676597ed557c88810', $this->job->getId());
    }

    public function testGetsSystemDescription(): void
    {
        $this->assertSame('Callback job: schedule-64ec6d2de2e52435fdb2cb0676597ed557c88810', $this->job->getSystemDescription());
    }

    public function testGetsSetsName(): void
    {
        $this->assertSame('callback: \'url\'', $this->job->getName());

        $this->job->setName('Callable job');
        $this->assertSame('Callable job', $this->job->getName());
    }

    public function testGetsExpression(): void
    {
        $this->assertSame('*/5 * * * *', $this->job->everyFiveMinutes()->getExpression());
    }

    public function testGetsSetsDescription(): void
    {
        $this->assertSame('Callable job description', $this->job->getDescription());

        $this->job->description($desc = 'Simple description');
        $this->assertSame($desc, $this->job->getDescription());
    }

    public function testRun(): void
    {
        $this->pinger->shouldReceive('ping')->once()->with('https://site.com')->andReturnTrue();
        $this->job->run($this->getContainer());
    }

    public function testRunInBackground(): void
    {
        $queueManager = $this->mockContainer(QueueConnectionProviderInterface::class);
        $queueManager->shouldReceive('getConnection')
            ->once()
            ->with('queue-test')
            ->andReturn($queue = m::mock(QueueInterface::class));
        $queue->shouldReceive('pushCallable')->once()->andReturnUsing(function (\Closure $closure) {
            $this->getContainer()->invoke($closure);
        });

        $this->mutex->shouldReceive('forget')
            ->once()
            ->with('schedule-64ec6d2de2e52435fdb2cb0676597ed557c88810');

        $this->pinger->shouldReceive('ping')->once()->with('https://site.com')->andReturnTrue();
        $this->job->runInBackground()->run($this->getContainer());
    }

    public function testRunInBackgroundWithoutOverlapping(): void
    {
        $this->mutex->shouldReceive('create')
            ->once()
            ->with('schedule-64ec6d2de2e52435fdb2cb0676597ed557c88810', 1500)
            ->andReturnTrue();

        $this->mutex->shouldReceive('forget')
            ->once()
            ->with('schedule-64ec6d2de2e52435fdb2cb0676597ed557c88810');

        $queueManager = $this->mockContainer(QueueConnectionProviderInterface::class);
        $queueManager->shouldReceive('getConnection')
            ->once()
            ->with('queue-test')
            ->andReturn($queue = m::mock(QueueInterface::class));
        $queue->shouldReceive('pushCallable')->once()->andReturnUsing(function (\Closure $closure) {
            $this->getContainer()->invoke($closure);
        });

        $this->pinger->shouldReceive('ping')->once()->with('https://site.com')->andReturnTrue();
        $this->job->withoutOverlapping(1500)->runInBackground()->run($this->getContainer());
    }

    public function testWithoutOverlapping(): void
    {
        $this->mutex->shouldReceive('create')
            ->once()
            ->with('schedule-64ec6d2de2e52435fdb2cb0676597ed557c88810', 1500)
            ->andReturnTrue();

        $this->mutex->shouldReceive('forget')
            ->once()
            ->with('schedule-64ec6d2de2e52435fdb2cb0676597ed557c88810');

        $this->pinger->shouldReceive('ping')->once()->with('https://site.com')->andReturnTrue();
        $this->job->withoutOverlapping(1500)->run($this->getContainer());
    }
}
