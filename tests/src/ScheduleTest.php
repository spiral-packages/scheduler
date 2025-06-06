<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Tests;

use Cron\CronExpression;
use Spiral\Scheduler\CommandRunner;
use Spiral\Scheduler\Mutex\JobMutexInterface;
use Spiral\Scheduler\ProcessFactory;
use Spiral\Scheduler\Schedule;
use Spiral\Scheduler\Testing\FakeJobRegistry;
use Spiral\Scheduler\Tests\App\Command\SimpleCommand;
use Symfony\Component\Process\PhpExecutableFinder;

final class ScheduleTest extends TestCase
{
    private Schedule $schedule;
    private FakeJobRegistry $registry;

    public function testRegisterCommand(): void
    {
        $job = $this->schedule
            ->command('foo:bar', ['baz' => 'biz'], 'Simple job')
            ->everyFifteenMinutes();

        $this->assertSame('/usr/bin/php app.php foo:bar baz=\'biz\'', $job->getName());
        $this->assertSame('Simple job', $job->getDescription());
        $this->assertSame('*/15 * * * *', $job->getExpression());

        $this->registry->assertRegisteredJob($job);
    }

    public function testRegisterCommandByClassname(): void
    {
        $job = $this->schedule
            ->command(SimpleCommand::class, ['baz' => 'biz'], 'Simple job')
            ->everyEvenMinute();

        $this->assertSame('/usr/bin/php app.php foo:bar baz=\'biz\'', $job->getName());
        $this->assertSame('Simple command', $job->getDescription());
        $this->assertSame('*/2 * * * *', $job->getExpression());

        $this->registry->assertRegisteredJob($job);
    }

    public function testRegisterCommandWithCronExpression(): void
    {
        $job = $this->schedule
            ->command(SimpleCommand::class, ['baz' => 'biz'], 'Simple job', new CronExpression('*/2 * * * *'));

        $this->assertSame('/usr/bin/php app.php foo:bar baz=\'biz\'', $job->getName());
        $this->assertSame('Simple command', $job->getDescription());
        $this->assertSame('*/2 * * * *', $job->getExpression());

        $this->registry->assertRegisteredJob($job);
    }

    public function testRegisterCallableJob(): void
    {
        $job = $this->schedule
            ->call('Simple callable job', static function (): void {}, ['baz' => 'biz'])
            ->everyFourMinutes();

        $this->assertSame('callback: \'baz\'', $job->getName());
        $this->assertSame('Simple callable job', $job->getDescription());
        $this->assertSame('*/4 * * * *', $job->getExpression());

        $this->registry->assertRegisteredJob($job);
    }

    public function testRegisterCallableJobWithExpression(): void
    {
        $job = $this->schedule->call(
            'Simple callable job',
            static function (): void {},
            ['baz' => 'biz'],
            new CronExpression('*/4 * * * *'),
        );

        $this->assertSame('callback: \'baz\'', $job->getName());
        $this->assertSame('Simple callable job', $job->getDescription());
        $this->assertSame('*/4 * * * *', $job->getExpression());

        $this->registry->assertRegisteredJob($job);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this->fakeScheduleJobRegistry();

        $this->schedule = new Schedule(
            $this->getContainer(),
            $this->mockContainer(ProcessFactory::class),
            $this->registry,
            new CommandRunner(
                $finder = $this->mockContainer(PhpExecutableFinder::class),
            ),
            $this->mockContainer(JobMutexInterface::class),
        );

        $finder->shouldReceive('find')->andReturn('/usr/bin/php');
    }
}
