<?php

declare(strict_types=1);

namespace Spiral\Scheduler;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Cache\CacheStorageProviderInterface;
use Spiral\Console\Bootloader\ConsoleBootloader;
use Spiral\Core\Container;
use Spiral\Scheduler\Config\SchedulerConfig;
use Spiral\Scheduler\Mutex\CacheEventMutex;
use Spiral\Scheduler\Mutex\EventMutexInterface;

class SchedulerBootloader extends Bootloader
{
    protected const DEPENDENCIES = [
        ConsoleBootloader::class,
    ];

    protected const SINGLETONS = [
        Schedule::class => [self::class, 'initSchedule'],
        EventMutexInterface::class => [self::class, 'initEventMutex'],
    ];

    public function boot(ConsoleBootloader $console): void
    {
        $console->addCommand(Commands\ScheduleRunCommand::class);
        $console->addCommand(Commands\ScheduleListCommand::class);
        $console->addCommand(Commands\ScheduleFinishCommand::class);
        $console->addCommand(Commands\ScheduleWorkCommand::class);
    }

    private function initSchedule(
        Container $container,
        CommandRunner $commandRunner,
        CommandBuilder $commandBuilder
    ): Schedule {
        return new Schedule(
            $container,
            $commandRunner,
            $commandBuilder,
            $container->get(EventMutexInterface::class),
            new \DateTimeZone('UTC'),
        );
    }

    private function initEventMutex(
        CacheStorageProviderInterface $provider,
        SchedulerConfig $config
    ): EventMutexInterface {
        return new CacheEventMutex(
            $provider->storage(
                $config->getCacheStorage()
            )
        );
    }
}