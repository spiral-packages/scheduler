<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Testing;

use Butschster\CronExpression\Generator;
use Carbon\Carbon;
use Spiral\Core\ContainerScope;
use Spiral\Scheduler\Commands\ScheduleRunCommand;
use Spiral\Scheduler\JobHandlerInterface;
use Spiral\Scheduler\JobRegistryInterface;
use Spiral\Scheduler\Mutex\JobMutexInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

trait InteractsWithSchedule
{
    public function runScheduler(string $expression)
    {
        $date = (new Generator())->cron($expression)->getExpression()->getNextRunDate();

        $this->runScoped(static function () use ($date): void {
            Carbon::setTestNow(Carbon::parse($date));

            $command = new ScheduleRunCommand();
            $command->setContainer(ContainerScope::getContainer());
            $command->run(new ArrayInput([]), new NullOutput());

            Carbon::setTestNow();
        }, [
            JobHandlerInterface::class => $handler = new FakeJobHandler(),
        ]);

        return $handler;
    }

    public function fakeScheduleJobHandler(): FakeJobHandler
    {
        $this->getContainer()->bindSingleton(
            JobHandlerInterface::class,
            $handler = new FakeJobHandler(),
        );

        return $handler;
    }

    public function fakeScheduleJobRegistry(): FakeJobRegistry
    {
        $this->getContainer()->bindSingleton(
            JobRegistryInterface::class,
            $registry = new FakeJobRegistry(),
        );

        return $registry;
    }

    public function fakeScheduleJobMutex(): FakeJobMutex
    {
        $this->getContainer()->bindSingleton(
            JobMutexInterface::class,
            $mutex = new FakeJobMutex(),
        );

        return $mutex;
    }
}
