<?php

declare(strict_types=1);

namespace Spiral\Scheduler\Bootloader;

use Cron\CronExpression;
use Spiral\Attributes\AttributeReader;
use Spiral\Boot\AbstractKernel;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Cache\Bootloader\CacheBootloader;
use Spiral\Cache\CacheStorageProviderInterface;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Console\Bootloader\ConsoleBootloader;
use Spiral\Core\FactoryInterface;
use Spiral\Scheduler\CommandRunner;
use Spiral\Scheduler\Commands;
use Spiral\Scheduler\Config\SchedulerConfig;
use Spiral\Scheduler\EveryMinuteCommandRunner;
use Spiral\Scheduler\JobHandler;
use Spiral\Scheduler\JobHandlerInterface;
use Spiral\Scheduler\JobRegistry;
use Spiral\Scheduler\JobRegistryInterface;
use Spiral\Scheduler\JobsLocator;
use Spiral\Scheduler\JobsLocatorInterface;
use Spiral\Scheduler\Mutex\CacheJobMutex;
use Spiral\Scheduler\Mutex\JobMutexInterface;
use Spiral\Scheduler\PeriodicCommandRunnerInterface;
use Spiral\Scheduler\ProcessFactory;
use Spiral\Scheduler\Schedule;
use Spiral\Tokenizer\Bootloader\TokenizerBootloader;
use Spiral\Tokenizer\ClassesInterface;

class SchedulerBootloader extends Bootloader
{
    protected const DEPENDENCIES = [
        ConsoleBootloader::class,
        TokenizerBootloader::class,
        CacheBootloader::class,
    ];

    protected const SINGLETONS = [
        JobHandlerInterface::class => JobHandler::class,
        PeriodicCommandRunnerInterface::class => EveryMinuteCommandRunner::class,
        Schedule::class => [self::class, 'initSchedule'],
        JobMutexInterface::class => [self::class, 'initEventMutex'],
        JobsLocatorInterface::class => JobsLocator::class,
        JobsLocator::class => [self::class, 'initJobsLocator'],
        JobRegistryInterface::class => JobRegistry::class,
        JobRegistry::class => JobRegistry::class,
    ];

    public function __construct(
        private readonly ConfiguratorInterface $config
    ) {
    }

    public function init(
        EnvironmentInterface $env,
        AbstractKernel $kernel,
        ConsoleBootloader $console
    ): void {
        $this->initConfig($env);

        $kernel->booting(static function (SchedulerConfig $config) {
            foreach ($config->getExpressionAliases() as $alias => $expression) {
                if (! CronExpression::supportsAlias($alias)) {
                    CronExpression::registerAlias($alias, $expression);
                }
            }
        });

        $kernel->booted(static function (JobsLocatorInterface $locator, JobRegistryInterface $registry): void {
            foreach ($locator->getJobs() as $job) {
                $registry->register($job);
            }
        });

        $console->addCommand(Commands\ScheduleRunCommand::class);
        $console->addCommand(Commands\ScheduleListCommand::class);
        $console->addCommand(Commands\ScheduleFinishCommand::class);
        $console->addCommand(Commands\ScheduleWorkCommand::class);
    }

    private function initSchedule(
        FactoryInterface $container,
        ProcessFactory $processFactory,
        JobRegistryInterface $registry,
        CommandRunner $commandRunner
    ): Schedule {
        return new Schedule(
            $container,
            $processFactory,
            $registry,
            $commandRunner,
            $container->get(JobMutexInterface::class),
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
