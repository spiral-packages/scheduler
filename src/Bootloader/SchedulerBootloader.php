<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Bootloader;

use Cron\CronExpression;
use Spiral\Attributes\AttributeReader;
use Spiral\Boot\AbstractKernel;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Cache\CacheStorageProviderInterface;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Console\Bootloader\ConsoleBootloader;
use Spiral\Core\Container;
use Spiral\Scheduler\CommandBuilder;
use Spiral\Scheduler\CommandRunner;
use Spiral\Scheduler\Commands;
use Spiral\Scheduler\Config\SchedulerConfig;
use Spiral\Scheduler\JobsLocator;
use Spiral\Scheduler\JobsLocatorInterface;
use Spiral\Scheduler\Mutex\CacheJobMutex;
use Spiral\Scheduler\Mutex\JobMutexInterface;
use Spiral\Scheduler\Schedule;
use Spiral\Tokenizer\Bootloader\TokenizerBootloader;
use Spiral\Tokenizer\ClassesInterface;

class SchedulerBootloader extends Bootloader
{
    protected const DEPENDENCIES = [
        ConsoleBootloader::class,
        TokenizerBootloader::class,
    ];

    protected const SINGLETONS = [
        Schedule::class => [self::class, 'initSchedule'],
        JobMutexInterface::class => [self::class, 'initEventMutex'],
        JobsLocatorInterface::class => JobsLocator::class,
        JobsLocator::class => [self::class, 'initJobsLocator'],
    ];

    public function __construct(private ConfiguratorInterface $config)
    {
    }

    public function boot(
        EnvironmentInterface $env,
        AbstractKernel $kernel,
        ConsoleBootloader $console
    ): void {
        $this->initConfig($env);

        $kernel->starting(static function (SchedulerConfig $config) {
            foreach ($config->getExpressionAliases() as $alias => $expression) {
                if (! CronExpression::supportsAlias($alias)) {
                    CronExpression::registerAlias($alias, $expression);
                }
            }
        });

        $kernel->started(static function (JobsLocatorInterface $locator, Schedule $schedule): void {
            foreach ($locator->getJobs() as $job) {
                $schedule->registerJob($job);
            }
        });

        $console->addCommand(Commands\ScheduleRunCommand::class);
        $console->addCommand(Commands\ScheduleListCommand::class);
        $console->addCommand(Commands\ScheduleFinishCommand::class);
        $console->addCommand(Commands\ScheduleWorkCommand::class);
    }

    private function initSchedule(
        Container $container,
        SchedulerConfig $config,
        CommandRunner $commandRunner,
        CommandBuilder $commandBuilder
    ): Schedule {
        return new Schedule(
            $container,
            $commandRunner,
            $commandBuilder,
            $container->get(JobMutexInterface::class),
            $config->getTimezone(),
        );
    }

    private function initJobsLocator(
        ClassesInterface $classes,
        JobMutexInterface $mutex
    ): JobsLocatorInterface {
        return new JobsLocator($classes, new AttributeReader(), $mutex);
    }

    private function initEventMutex(
        CacheStorageProviderInterface $provider,
        SchedulerConfig $config
    ): JobMutexInterface {
        return new CacheJobMutex(
            $provider->storage(
                $config->getCacheStorage()
            )
        );
    }

    private function initConfig(EnvironmentInterface $env)
    {
        $this->config->setDefaults(SchedulerConfig::CONFIG, [
            'cacheStorage' => $env->get('SCHEDULER_MUTEX_CACHE_STORAGE'),
            'queueConnection' => $env->get('SCHEDULER_QUEUE_CONNECTION'),
            'timezone' => $env->get('SCHEDULER_TIMEZONE', 'UTC'),
            'expression' => [
                'aliases' => [
                    // '@yearly' => '0 0 1 1 *',
                ],
            ],
        ]);
    }
}
